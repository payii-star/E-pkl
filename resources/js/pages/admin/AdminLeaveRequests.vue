<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title d-flex flex-column">
                        <h2 class="fw-bold mb-1">Kelola Izin</h2>
                        <div class="text-gray-500 fs-7">{{ pendingCount }} pengajuan menunggu persetujuan</div>
                    </div>
                    <div class="card-toolbar">
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

                    <div v-else-if="!leaveRequests.length" class="text-center text-muted py-10">
                        Belum ada pengajuan izin.
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
                                <tr v-for="lr in leaveRequests" :key="lr.id">
                                    <td>
                                        <div class="fw-semibold">{{ lr.user?.name ?? '-' }}</div>
                                        <div class="text-muted fs-8">{{ lr.user?.email ?? '' }}</div>
                                    </td>
                                    <td>{{ lr.date }}</td>
                                    <td>{{ reasonLabel[lr.reason_type] }}</td>
                                    <td class="text-truncate" style="max-width:220px">{{ lr.note ?? '-' }}</td>
                                    <td>
                                        <a v-if="lr.attachment" :href="attachmentUrl(lr.attachment)" target="_blank" class="text-primary">
                                            Lihat
                                        </a>
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

const pendingCount = computed(
    () => leaveRequests.value.filter((lr) => lr.status === 'pending').length
)

function attachmentUrl(path: string) {
    return `${BACKEND_URL}/storage/${path}`
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