<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Penilaian & Rekap Magang</h2>
                    </div>
                    <div class="card-toolbar d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label fs-8 mb-1">Dari</label>
                            <input v-model="period.start" type="date" class="form-control form-control-sm" />
                        </div>
                        <div>
                            <label class="form-label fs-8 mb-1">Sampai</label>
                            <input v-model="period.end" type="date" class="form-control form-control-sm" />
                        </div>
                        <button class="btn btn-sm btn-light-primary" @click="setThisWeek">Minggu Ini</button>
                        <button class="btn btn-sm btn-light-primary" @click="setLastWeek">Minggu Lalu</button>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div v-else-if="!interns.length" class="text-center text-muted py-10">
                        Belum ada peserta magang.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-row-bordered align-middle">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Nama</th>
                                    <th class="text-center">Poin Disiplin</th>
                                    <th class="text-center">Poin Tugas</th>
                                    <th class="text-center">Tugas Selesai</th>
                                    <th class="text-center">Nilai Akhir</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in interns" :key="row.user.id">
                                    <td>
                                        <div class="fw-bold text-gray-800">{{ row.user.name }}</div>
                                        <div class="text-muted fs-8">{{ row.user.email }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span :class="row.discipline_points < 0 ? 'text-danger' : 'text-success'" class="fw-bold">
                                            {{ row.discipline_points > 0 ? '+' : '' }}{{ row.discipline_points }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span :class="row.task_points < 0 ? 'text-danger' : 'text-success'" class="fw-bold">
                                            {{ row.task_points > 0 ? '+' : '' }}{{ row.task_points }}
                                        </span>
                                    </td>
                                    <td class="text-center fs-7">
                                        {{ row.task_summary.completed }} / {{ row.task_summary.total }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge fs-6" :class="scoreBadgeClass(row.final_score)">
                                            {{ row.final_score }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light-primary" @click="openDetail(row.user)">
                                            Detail & Rekap
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODAL DETAIL & REKAP ══ -->
    <div v-if="selectedUser" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">{{ selectedUser.name }}</h5>
                        <div class="text-muted fs-8">{{ formatDate(period.start) }} — {{ formatDate(period.end) }}</div>
                    </div>
                    <button class="btn btn-sm btn-icon btn-light" @click="closeDetail">✕</button>
                </div>

                <div class="modal-body">
                    <div v-if="detailLoading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <template v-else-if="detail">
                        <!-- Ringkasan nilai -->
                        <div class="d-flex gap-3 mb-5 flex-wrap">
                            <div class="flex-fill bg-light rounded p-3 text-center">
                                <div class="text-muted fs-8 fw-semibold mb-1">POIN DISIPLIN</div>
                                <div class="fw-bold fs-4" :class="detail.discipline_points < 0 ? 'text-danger' : 'text-success'">
                                    {{ detail.discipline_points > 0 ? '+' : '' }}{{ detail.discipline_points }}
                                </div>
                            </div>
                            <div class="flex-fill bg-light rounded p-3 text-center">
                                <div class="text-muted fs-8 fw-semibold mb-1">POIN TUGAS</div>
                                <div class="fw-bold fs-4" :class="detail.task_points < 0 ? 'text-danger' : 'text-success'">
                                    {{ detail.task_points > 0 ? '+' : '' }}{{ detail.task_points }}
                                </div>
                            </div>
                            <div class="flex-fill rounded p-3 text-center" :class="scoreBgClass(detail.final_score)">
                                <div class="text-muted fs-8 fw-semibold mb-1">NILAI AKHIR</div>
                                <div class="fw-bold fs-4">{{ detail.final_score }}</div>
                            </div>
                        </div>

                        <!-- Ringkasan tugas -->
                        <div class="border rounded p-3 mb-5">
                            <div class="fw-semibold fs-7 mb-2">Progres Tugas Periode Ini</div>
                            <div class="d-flex flex-wrap gap-4 fs-7 text-gray-700">
                                <div>Total tugas: <b>{{ detail.task_summary.total }}</b></div>
                                <div>Selesai: <b class="text-success">{{ detail.task_summary.completed }}</b></div>
                                <div>Belum dikerjakan (lewat deadline): <b class="text-danger">{{ detail.task_summary.not_completed }}</b></div>
                                <div v-if="detail.task_summary.avg_submission_delta_hours !== null">
                                    Rata-rata submit:
                                    <b :class="detail.task_summary.avg_submission_delta_hours >= 0 ? 'text-success' : 'text-danger'">
                                        {{ formatDelta(detail.task_summary.avg_submission_delta_hours) }}
                                    </b>
                                    dari deadline
                                </div>
                            </div>
                        </div>

                        <!-- Form tambah / edit catatan rekap -->
                        <div class="border rounded p-3 mb-5 bg-light-primary bg-opacity-25">
                            <div class="fw-semibold fs-7 mb-3">
                                {{ isEditingRecord ? 'Edit Catatan Rekap' : 'Tambah Catatan Rekap Harian' }}
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fs-8 fw-semibold">Tanggal</label>
                                    <input v-model="recordForm.date" type="date" class="form-control form-control-sm" />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-8 fw-semibold">Jenis</label>
                                    <select v-model="recordForm.type" class="form-select form-select-sm" @change="onTypeChange">
                                        <option value="telat">Terlambat</option>
                                        <option value="pulang_cepat">Pulang Lebih Awal</option>
                                        <option value="tanpa_keterangan">Tanpa Keterangan</option>
                                        <option value="tidak_kerjakan_tugas">Tidak Mengerjakan Tugas</option>
                                        <option value="bonus">Nilai Tambahan (Bonus)</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-8 fw-semibold">Poin</label>
                                    <input v-model.number="recordForm.points" type="number" class="form-control form-control-sm" />
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fs-8 fw-semibold">Catatan (opsional)</label>
                                    <textarea v-model="recordForm.note" class="form-control form-control-sm" rows="2" placeholder="Detail tambahan..."></textarea>
                                </div>
                                <div v-if="recordFormMsg" class="col-md-12">
                                    <div class="alert alert-danger py-2 fs-7 mb-0">{{ recordFormMsg }}</div>
                                </div>
                                <div class="col-md-12 d-flex gap-2 justify-content-end">
                                    <button v-if="isEditingRecord" class="btn btn-sm btn-light" @click="cancelEditRecord">
                                        Batal Edit
                                    </button>
                                    <button class="btn btn-sm btn-primary" :disabled="savingRecord" @click="submitRecord">
                                        <span v-if="savingRecord" class="spinner-border spinner-border-sm me-2"></span>
                                        {{ isEditingRecord ? 'Simpan Perubahan' : 'Tambah Catatan' }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Daftar catatan rekap -->
                        <div class="fw-semibold fs-7 mb-2">Riwayat Catatan Periode Ini</div>
                        <div v-if="!detail.records.length" class="text-muted fs-7 text-center py-5">
                            Belum ada catatan rekap untuk periode ini.
                        </div>
                        <div v-else class="d-flex flex-column gap-2">
                            <div v-for="rec in detail.records" :key="rec.id" class="d-flex justify-content-between align-items-start border rounded p-2 px-3">
                                <div>
                                    <div class="fs-7">
                                        <span class="badge me-2" :class="typeBadgeClass(rec.type)">{{ typeLabel[rec.type] }}</span>
                                        <span class="text-muted">{{ formatDate(rec.date) }}</span>
                                    </div>
                                    <div v-if="rec.note" class="text-muted fs-8 mt-1">{{ rec.note }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold" :class="rec.points < 0 ? 'text-danger' : 'text-success'">
                                        {{ rec.points > 0 ? '+' : '' }}{{ rec.points }}
                                    </span>
                                    <button class="btn btn-sm btn-icon btn-light-primary" @click="editRecord(rec)">
                                        <KTIcon icon-name="pencil" icon-class="fs-6" />
                                    </button>
                                    <button class="btn btn-sm btn-icon btn-light-danger" :disabled="deletingRecordId === rec.id" @click="deleteRecord(rec)">
                                        <span v-if="deletingRecordId === rec.id" class="spinner-border spinner-border-sm"></span>
                                        <KTIcon v-else icon-name="trash" icon-class="fs-6" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="closeDetail">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '@/libs/axios'
import { toast } from 'vue3-toastify'

interface InternRow {
    user: { id: number; name: string; email: string; photo?: string | null }
    discipline_points: number
    task_points: number
    final_score: number
    task_summary: { total: number; completed: number; not_completed: number; avg_submission_delta_hours: number | null }
}

interface DisciplineRecordItem {
    id: number
    date: string
    type: string
    points: number
    note: string | null
}

const interns = ref<InternRow[]>([])
const loading = ref(false)

const period = ref({
    start: '',
    end: '',
})

const selectedUser = ref<{ id: number; name: string } | null>(null)
const detail = ref<any>(null)
const detailLoading = ref(false)

const defaultPoints: Record<string, number> = {
    telat: -5,
    pulang_cepat: -5,
    tanpa_keterangan: -10,
    tidak_kerjakan_tugas: -10,
    bonus: 5,
    lainnya: 0,
}

const typeLabel: Record<string, string> = {
    telat: 'Terlambat',
    pulang_cepat: 'Pulang Lebih Awal',
    tanpa_keterangan: 'Tanpa Keterangan',
    tidak_kerjakan_tugas: 'Tidak Mengerjakan Tugas',
    bonus: 'Bonus',
    lainnya: 'Lainnya',
}

const recordForm = ref({
    date: '',
    type: 'telat',
    points: defaultPoints.telat,
    note: '',
})
const recordFormMsg = ref('')
const savingRecord = ref(false)
const isEditingRecord = ref(false)
const editingRecordId = ref<number | null>(null)
const deletingRecordId = ref<number | null>(null)

function toDateInput(d: Date) {
    return d.toISOString().slice(0, 10)
}

function setThisWeek() {
    const now = new Date()
    const day = now.getDay() || 7 // Minggu = 0 -> jadi 7
    const monday = new Date(now)
    monday.setDate(now.getDate() - day + 1)
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)
    period.value = { start: toDateInput(monday), end: toDateInput(sunday) }
    loadInterns()
}

function setLastWeek() {
    const now = new Date()
    const day = now.getDay() || 7
    const monday = new Date(now)
    monday.setDate(now.getDate() - day + 1 - 7)
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)
    period.value = { start: toDateInput(monday), end: toDateInput(sunday) }
    loadInterns()
}

function formatDate(dateStr: string) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDelta(hours: number) {
    const abs = Math.abs(hours)
    const label = hours >= 0 ? 'lebih cepat' : 'lebih lambat'
    if (abs < 24) return `${abs.toFixed(1)} jam ${label}`
    return `${(abs / 24).toFixed(1)} hari ${label}`
}

function scoreBadgeClass(score: number) {
    if (score >= 85) return 'badge-light-success'
    if (score >= 70) return 'badge-light-warning'
    return 'badge-light-danger'
}

function scoreBgClass(score: number) {
    if (score >= 85) return 'bg-light-success'
    if (score >= 70) return 'bg-light-warning'
    return 'bg-light-danger'
}

function typeBadgeClass(type: string) {
    if (type === 'bonus') return 'badge-light-success'
    if (type === 'lainnya') return 'badge-light-secondary'
    return 'badge-light-danger'
}

async function loadInterns() {
    loading.value = true
    try {
        const res = await axios.get('/admin/assessments', { params: period.value })
        interns.value = res.data?.data ?? []
    } catch (e) {
        console.error('Gagal memuat rekap penilaian:', e)
        toast.error('Gagal memuat data rekap penilaian')
    } finally {
        loading.value = false
    }
}

async function openDetail(user: { id: number; name: string }) {
    selectedUser.value = user
    detail.value = null
    resetRecordForm()
    await loadDetail()
}

async function loadDetail() {
    if (!selectedUser.value) return
    detailLoading.value = true
    try {
        const res = await axios.get(`/admin/assessments/${selectedUser.value.id}`, { params: period.value })
        detail.value = res.data?.data ?? null
    } catch (e) {
        console.error('Gagal memuat detail penilaian:', e)
        toast.error('Gagal memuat detail penilaian siswa ini')
    } finally {
        detailLoading.value = false
    }
}

function closeDetail() {
    selectedUser.value = null
    detail.value = null
    resetRecordForm()
}

function resetRecordForm() {
    recordForm.value = {
        date: toDateInput(new Date()),
        type: 'telat',
        points: defaultPoints.telat,
        note: '',
    }
    recordFormMsg.value = ''
    isEditingRecord.value = false
    editingRecordId.value = null
}

function onTypeChange() {
    // Auto-isi poin default sesuai jenis, admin masih bisa override manual
    recordForm.value.points = defaultPoints[recordForm.value.type] ?? 0
}

function editRecord(rec: DisciplineRecordItem) {
    isEditingRecord.value = true
    editingRecordId.value = rec.id
    recordForm.value = {
        date: rec.date.slice(0, 10),
        type: rec.type,
        points: rec.points,
        note: rec.note ?? '',
    }
    recordFormMsg.value = ''
}

function cancelEditRecord() {
    resetRecordForm()
}

async function submitRecord() {
    if (!selectedUser.value) return
    if (!recordForm.value.date || !recordForm.value.type) {
        recordFormMsg.value = 'Tanggal dan jenis wajib diisi'
        return
    }

    savingRecord.value = true
    recordFormMsg.value = ''
    try {
        if (isEditingRecord.value && editingRecordId.value) {
            await axios.put(`/admin/assessments/records/${editingRecordId.value}`, recordForm.value)
            toast.success('Catatan rekap berhasil diperbarui')
        } else {
            await axios.post(`/admin/assessments/${selectedUser.value.id}/records`, recordForm.value)
            toast.success('Catatan rekap berhasil ditambahkan')
        }
        resetRecordForm()
        await loadDetail()
        await loadInterns()
    } catch (e: any) {
        recordFormMsg.value = e.response?.data?.message ?? 'Gagal menyimpan catatan rekap'
    } finally {
        savingRecord.value = false
    }
}

async function deleteRecord(rec: DisciplineRecordItem) {
    if (!confirm('Hapus catatan rekap ini?')) return
    deletingRecordId.value = rec.id
    try {
        await axios.delete(`/admin/assessments/records/${rec.id}`)
        toast.success('Catatan rekap berhasil dihapus')
        await loadDetail()
        await loadInterns()
    } catch (e: any) {
        toast.error(e.response?.data?.message ?? 'Gagal menghapus catatan rekap')
    } finally {
        deletingRecordId.value = null
    }
}

onMounted(() => {
    setThisWeek()
})
</script>