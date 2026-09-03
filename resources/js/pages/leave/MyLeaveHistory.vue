<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Riwayat Izin</h2>
                    </div>
                    <div class="card-toolbar d-flex gap-3">
                        <select class="form-select form-select-sm w-150px" v-model="period">
                            <option value="month">Bulanan</option>
                            <option value="week">Mingguan</option>
                        </select>
                        <input
                            v-if="period === 'month'"
                            v-model="selectedMonth"
                            type="month"
                            class="form-control form-control-sm w-150px"
                            aria-label="Pilih bulan riwayat izin"
                        />
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Memuat riwayat izin...
                    </div>

                    <div v-else-if="!filteredHistoryRequests.length" class="text-center text-muted py-10">
                        Tidak ada riwayat izin pada periode yang dipilih.
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-row-dashed align-middle gy-4">
                            <thead>
                                <tr class="fw-bold text-gray-500 fs-7 text-uppercase">
                                    <th>Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Catatan</th>
                                    <th>Lampiran</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lr in filteredHistoryRequests" :key="lr.id">
                                    <td>{{ formatDate(lr.date) }}</td>
                                    <td>{{ reasonLabel[lr.reason_type] }}</td>
                                    <td>{{ lr.note ?? '-' }}</td>
                                    <td>
                                        <a
                                            v-if="lr.attachment && isImage(lr.attachment)"
                                            href="#"
                                            class="text-primary"
                                            @click.prevent="openImagePreview(lr.attachment)"
                                        >
                                            Lihat
                                        </a>
                                        <a
                                            v-else-if="lr.attachment"
                                            :href="attachmentUrl(lr.attachment)"
                                            target="_blank"
                                            class="text-primary"
                                        >
                                            Lihat (PDF)
                                        </a>
                                        <span v-else>-</span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="statusBadge[lr.status]">
                                            {{ statusLabel[lr.status] }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PREVIEW FOTO LAMPIRAN -->
    <div v-if="previewImageUrl" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.7)" @click.self="closeImagePreview">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 bg-transparent shadow-none">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-icon btn-light" @click="closeImagePreview">✕</button>
                </div>
                <img :src="previewImageUrl" class="rounded shadow w-100" style="max-height:80vh; object-fit:contain; background:#000;" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '@/libs/axios'
import { toast } from 'vue3-toastify'
import { BACKEND_URL } from '@/libs/env'

interface LeaveRequest {
    id: number
    date: string
    reason_type: 'tanpa_keterangan' | 'sakit' | 'acara_keluarga'
    note: string | null
    attachment: string | null
    status: 'pending' | 'approved' | 'rejected'
}

const leaveRequests = ref<LeaveRequest[]>([])
const loading = ref(false)
const previewImageUrl = ref<string | null>(null)
const period = ref<'week' | 'month'>('month')
const selectedMonth = ref(currentMonthValue())
const selectedWeekDate = ref(currentDateValue())

// Cuma tampilin izin yang statusnya sudah diproses (bukan pending)
const historyRequests = computed(() => leaveRequests.value.filter((lr) => lr.status !== 'pending'))
const filteredHistoryRequests = computed(() => {
    if (period.value === 'month') {
        return historyRequests.value.filter((lr) => dateKey(lr.date).startsWith(selectedMonth.value))
    }

    const selectedDate = parseDate(selectedWeekDate.value)
    const dayOfWeek = selectedDate.getDay() || 7
    selectedDate.setDate(selectedDate.getDate() - dayOfWeek + 1)
    const weekStart = dateKeyFromDate(selectedDate)
    selectedDate.setDate(selectedDate.getDate() + 6)
    const weekEnd = dateKeyFromDate(selectedDate)

    return historyRequests.value.filter((lr) => {
        const date = dateKey(lr.date)
        return date >= weekStart && date <= weekEnd
    })
})

const reasonLabel: Record<string, string> = {
    tanpa_keterangan: 'Tanpa Keterangan',
    sakit: 'Sakit (Surat Dokter)',
    acara_keluarga: 'Acara Keluarga',
}

const statusLabel: Record<string, string> = {
    pending: 'Menunggu Persetujuan',
    approved: 'Disetujui',
    rejected: 'Ditolak',
}

const statusBadge: Record<string, string> = {
    pending: 'badge-light-warning',
    approved: 'badge-light-success',
    rejected: 'badge-light-danger',
}

function formatDate(dateStr: string | null) {
    if (!dateStr) return '-'
    const [year, month, day] = dateKey(dateStr).split('-').map(Number)
    return new Date(year, month - 1, day).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

function currentMonthValue() {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function currentDateValue() {
    const now = new Date()
    return dateKeyFromDate(now)
}

function parseDate(dateStr: string) {
    const [year, month, day] = dateStr.slice(0, 10).split('-').map(Number)
    return new Date(year, month - 1, day)
}

function dateKey(dateStr: string) {
    return dateStr.slice(0, 10)
}

function dateKeyFromDate(date: Date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}

function attachmentUrl(path: string) {
    return `${BACKEND_URL}/storage/${path}`
}

function isImage(path: string) {
    return /\.(jpe?g|png|webp|gif)$/i.test(path)
}

function openImagePreview(path: string) {
    previewImageUrl.value = attachmentUrl(path)
}

function closeImagePreview() {
    previewImageUrl.value = null
}

function fetchLeaveRequests() {
    loading.value = true
    axios
        .get('/leave-requests')
        .then(({ data }) => {
            leaveRequests.value = data.data ?? []
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? 'Gagal memuat riwayat izin')
        })
        .finally(() => {
            loading.value = false
        })
}

onMounted(fetchLeaveRequests)
</script>