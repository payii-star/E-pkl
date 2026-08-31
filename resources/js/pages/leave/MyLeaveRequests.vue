<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Izin Tidak Masuk</h2>
                    </div>
                    <div class="card-toolbar">
                        <button class="btn btn-sm btn-primary" @click="showForm = !showForm">
                            {{ showForm ? 'Batal' : 'Ajukan Izin' }}
                        </button>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <!-- FORM PENGAJUAN -->
                    <div v-if="showForm" class="border rounded p-5 mb-6 bg-light-primary bg-opacity-25">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-7">Tanggal Tidak Masuk</label>
                                <input type="date" class="form-control form-control-solid" v-model="form.date" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-7">Alasan</label>
                                <select class="form-select form-select-solid" v-model="form.reason_type">
                                    <option value="" disabled>Pilih alasan</option>
                                    <option value="tanpa_keterangan">Tanpa Keterangan</option>
                                    <option value="sakit">Sakit (Surat Dokter)</option>
                                    <option value="acara_keluarga">Acara Keluarga</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold fs-7">Catatan (opsional)</label>
                                <textarea
                                    class="form-control form-control-solid"
                                    rows="3"
                                    v-model="form.note"
                                    placeholder="Detail tambahan (opsional)"
                                ></textarea>
                            </div>
                            <div class="col-md-12" v-if="form.reason_type === 'sakit'">
                                <label class="form-label fw-bold fs-7 required">Lampiran Surat Dokter</label>
                                <input
                                    type="file"
                                    class="form-control form-control-solid"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    @change="onFileChange"
                                />
                                <div class="text-muted fs-8 mt-1">Format JPG/PNG/PDF, maks 2MB</div>
                            </div>
                            <div v-if="formMsg" class="col-md-12">
                                <div class="alert alert-danger py-2 fs-7 mb-0">{{ formMsg }}</div>
                            </div>
                            <div class="col-md-12 text-end">
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    :disabled="submitting"
                                    @click="submit"
                                >
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    Kirim Pengajuan
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RIWAYAT IZIN -->
                    <div v-if="loading" class="text-center py-10">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Memuat riwayat izin...
                    </div>

                    <div v-else-if="!leaveRequests.length" class="text-center text-muted py-10">
                        Belum pernah mengajukan izin.
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
                                <tr v-for="lr in leaveRequests" :key="lr.id">
                                    <td>{{ lr.date }}</td>
                                    <td>{{ reasonLabel[lr.reason_type] }}</td>
                                    <td>{{ lr.note ?? '-' }}</td>
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
import { ref, onMounted } from 'vue'
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
const submitting = ref(false)
const showForm = ref(false)
const formMsg = ref('')
const attachmentFile = ref<File | null>(null)

const form = ref({
    date: '',
    reason_type: '',
    note: '',
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

function attachmentUrl(path: string) {
    return `${BACKEND_URL}/storage/${path}`
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement
    attachmentFile.value = target.files?.[0] ?? null
}

function resetForm() {
    form.value = { date: '', reason_type: '', note: '' }
    attachmentFile.value = null
    formMsg.value = ''
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

function submit() {
    formMsg.value = ''

    if (!form.value.date || !form.value.reason_type) {
        formMsg.value = 'Tanggal dan alasan wajib diisi'
        return
    }
    if (form.value.reason_type === 'sakit' && !attachmentFile.value) {
        formMsg.value = 'Lampiran surat dokter wajib diunggah untuk alasan sakit'
        return
    }

    const formData = new FormData()
    formData.append('date', form.value.date)
    formData.append('reason_type', form.value.reason_type)
    formData.append('note', form.value.note ?? '')
    if (attachmentFile.value) {
        formData.append('attachment', attachmentFile.value)
    }

    submitting.value = true
    axios
        .post('/leave-requests', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        .then(() => {
            toast.success('Pengajuan izin berhasil dikirim')
            showForm.value = false
            resetForm()
            fetchLeaveRequests()
        })
        .catch((err: any) => {
            formMsg.value = err.response?.data?.message ?? 'Gagal mengirim pengajuan izin'
        })
        .finally(() => {
            submitting.value = false
        })
}

onMounted(fetchLeaveRequests)
</script>