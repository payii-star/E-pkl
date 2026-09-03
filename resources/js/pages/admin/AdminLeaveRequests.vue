<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title d-flex flex-column">
                        <h2 class="fw-bold mb-1">Kelola Izin</h2>
                        <div class="text-gray-500 fs-7">{{ pendingCount }} pengajuan menunggu persetujuan</div>
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
                            aria-label="Pilih bulan pengajuan izin"
                        />
                        <select class="form-select form-select-sm w-150px" v-model="filterStatus" @change="fetchLeaveRequests">
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div v-else-if="!filteredLeaveRequests.length" class="text-center text-muted py-10">
                        Tidak ada pengajuan izin pada periode yang dipilih.
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-row-bordered align-middle">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Intern</th>
                                    <th>Tanggal</th>
                                    <th>Alasan</th>
                                    <th>Catatan</th>
                                    <th>Lampiran</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lr in filteredLeaveRequests" :key="lr.id">
                                    <td>
                                        <div class="fw-semibold">{{ lr.user?.name ?? '-' }}</div>
                                        <div class="text-muted fs-8">{{ lr.user?.email ?? '' }}</div>
                                    </td>
                                    <td>{{ lr.date }}</td>
                                    <td>{{ reasonLabel[lr.reason_type] }}</td>
                                    <td class="text-truncate" style="max-width:220px">{{ lr.note ?? '-' }}</td>
                                    <td>
                                        <button
                                            v-if="lr.attachment"
                                            type="button"
                                            class="btn btn-link btn-sm p-0 text-primary"
                                            @click="openImagePreview(lr.attachment)"
                                        >
                                            Lihat
                                        </button>
                                        <span v-else>-</span>
                                    </td>
                                    <td>
                                        <span class="badge" :class="statusBadge[lr.status]">
                                            {{ statusLabel[lr.status] }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <template v-if="lr.status === 'pending'">
                                            <button
                                                class="btn btn-sm btn-light-success me-2"
                                                :disabled="updatingId === lr.id"
                                                @click="review(lr, 'approved')"
                                            >
                                                Setujui
                                            </button>
                                            <button
                                                class="btn btn-sm btn-light-danger"
                                                :disabled="updatingId === lr.id"
                                                @click="review(lr, 'rejected')"
                                            >
                                                Tolak
                                            </button>
                                        </template>
                                        <span v-else class="text-muted fs-8">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="previewImageUrl"
        class="modal fade show d-block"
        tabindex="-1"
        style="background: rgba(0, 0, 0, 0.7)"
        @click.self="closeImagePreview"
    >
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 bg-transparent shadow-none">
                <div class="d-flex justify-content-end mb-2">
                    <button
                        type="button"
                        class="btn btn-icon btn-light"
                        aria-label="Tutup preview foto"
                        @click="closeImagePreview"
                    >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="card shadow-sm overflow-hidden">
                    <img
                        :src="previewImageUrl"
                        alt="Preview foto lampiran izin"
                        class="w-100"
                        style="max-height: 80vh; object-fit: contain; background: #000"
                    />
                </div>
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
    user?: { id: number; name: string; email: string }
}

const leaveRequests = ref<LeaveRequest[]>([])
const loading = ref(false)
const updatingId = ref<number | null>(null)
const filterStatus = ref('')
const previewImageUrl = ref<string | null>(null)
const period = ref<'week' | 'month'>('month')
const selectedMonth = ref(currentMonthValue())
const selectedWeekDate = ref(currentDateValue())

const reasonLabel: Record<string, string> = {
    tanpa_keterangan: 'Tanpa Keterangan',
    sakit: 'Sakit (Surat Dokter)',
    acara_keluarga: 'Acara Keluarga',
}

const statusLabel: Record<string, string> = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
}

const statusBadge: Record<string, string> = {
    pending: 'badge-light-warning',
    approved: 'badge-light-success',
    rejected: 'badge-light-danger',
}

const filteredLeaveRequests = computed(() => {
    if (period.value === 'month') {
        return leaveRequests.value.filter((lr) => dateKey(lr.date).startsWith(selectedMonth.value))
    }

    const selectedDate = parseDate(selectedWeekDate.value)
    const dayOfWeek = selectedDate.getDay() || 7
    selectedDate.setDate(selectedDate.getDate() - dayOfWeek + 1)
    const weekStart = dateKeyFromDate(selectedDate)
    selectedDate.setDate(selectedDate.getDate() + 6)
    const weekEnd = dateKeyFromDate(selectedDate)

    return leaveRequests.value.filter((lr) => {
        const date = dateKey(lr.date)
        return date >= weekStart && date <= weekEnd
    })
})

const pendingCount = computed(
    () => filteredLeaveRequests.value.filter((lr) => lr.status === 'pending').length
)

function attachmentUrl(path: string) {
    return `${BACKEND_URL}/storage/${path}`
}

function openImagePreview(path: string) {
    previewImageUrl.value = attachmentUrl(path)
}

function closeImagePreview() {
    previewImageUrl.value = null
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

function fetchLeaveRequests() {
    loading.value = true
    axios
        .get('/admin/leave-requests', { params: { status: filterStatus.value || undefined } })
        .then(({ data }) => {
            leaveRequests.value = data.data ?? []
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? 'Gagal memuat daftar izin')
        })
        .finally(() => {
            loading.value = false
        })
}

function review(lr: LeaveRequest, status: 'approved' | 'rejected') {
    updatingId.value = lr.id
    axios
        .patch(`/admin/leave-requests/${lr.id}/status`, { status })
        .then(({ data }) => {
            lr.status = data.data.status
            toast.success(data.message)
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? 'Gagal memperbarui status')
        })
        .finally(() => {
            updatingId.value = null
        })
}

onMounted(fetchLeaveRequests)
</script>