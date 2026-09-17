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
                            <label class="form-label fs-8 mb-1">Tanggal</label>
                            <input v-model="selectedDate" type="date" class="form-control form-control-sm" @change="loadInterns" />
                        </div>
                        <button class="btn btn-sm btn-light-primary" @click="setToday">Hari Ini</button>
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
                                    <th class="text-center">Absen Masuk</th>
                                    <th class="text-center">Absen Pulang</th>
                                    <th class="text-center">Tugas</th>
                                    <th class="text-center">Nilai Sistem</th>
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
                                    <td class="text-center fs-7">
                                        <span v-if="row.attendance.check_in_time">
                                            {{ row.attendance.check_in_time }}
                                            <span v-if="row.attendance.is_late" class="badge badge-light-danger ms-1">
                                                Telat{{ row.attendance.late_minutes > 0 ? ` ${row.attendance.late_minutes}m` : '' }}
                                            </span>
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-center fs-7">
                                        <span v-if="row.attendance.check_out_time">
                                            {{ row.attendance.check_out_time }}
                                            <span v-if="row.attendance.is_early_leave" class="badge badge-light-danger ms-1">
                                                Cepat{{ row.attendance.early_minutes > 0 ? ` ${row.attendance.early_minutes}m` : '' }}
                                            </span>
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-center fs-7">
                                        {{ row.task_summary.completed }}/{{ row.task_summary.total_assigned }}
                                        <span v-if="row.task_summary.overdue_incomplete > 0" class="badge badge-light-danger ms-1">
                                            {{ row.task_summary.overdue_incomplete }} telat
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="row.has_period" class="badge fs-6" :class="scoreBadgeClass(row.system_score)">
                                            {{ row.system_score }}
                                        </span>
                                        <span v-else class="badge badge-light-secondary fs-8">Periode belum diatur</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="row.assessment" class="badge fs-6" :class="scoreBadgeClass(row.assessment.score)">
                                            {{ row.assessment.score }}
                                        </span>
                                        <span v-else class="badge badge-light-secondary fs-7">Belum dinilai</span>
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
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">{{ selectedUser.name }}</h5>
                        <div class="text-muted fs-8">Data s.d. {{ formatDate(selectedDate) }}</div>
                    </div>
                    <button class="btn btn-sm btn-icon btn-light" @click="closeDetail">✕</button>
                </div>

                <div class="modal-body">
                    <div v-if="detailLoading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <template v-else-if="detail">
                        <!-- Peringatan kalau periode magang belum diatur -->
                        <div v-if="!detail.has_period" class="alert alert-warning fs-7 mb-5">
                            Periode magang siswa ini belum diatur, sehingga <b>nilai sistem tidak dapat dihitung</b>.
                            Atur dulu tanggal mulai & selesai magangnya di menu <b>Periode Magang</b>.
                        </div>

                        <template v-else>
                            <div class="text-muted fs-8 mb-4">
                                Periode magang: {{ formatDate(detail.period.start) }} — {{ formatDate(detail.period.end) }}
                                (hari ke-{{ detail.score_breakdown.elapsed_days }} dari {{ detail.score_breakdown.total_days }})
                            </div>

                            <!-- Breakdown nilai sistem -->
                            <div class="row g-3 mb-5">
                                <div class="col-6 col-md-3">
                                    <div class="bg-light rounded p-3 text-center">
                                        <div class="text-muted fs-8 fw-semibold mb-1">BASE (WAKTU)</div>
                                        <div class="fw-bold fs-5">{{ detail.score_breakdown.base_score }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="rounded p-3 text-center" :class="detail.score_breakdown.task_deduction > 0 ? 'bg-light-danger' : 'bg-light'">
                                        <div class="text-muted fs-8 fw-semibold mb-1">POTONGAN TUGAS</div>
                                        <div class="fw-bold fs-5">-{{ detail.score_breakdown.task_deduction }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="rounded p-3 text-center" :class="(detail.score_breakdown.late_deduction + detail.score_breakdown.early_deduction) > 0 ? 'bg-light-danger' : 'bg-light'">
                                        <div class="text-muted fs-8 fw-semibold mb-1">POTONGAN TELAT/CEPAT</div>
                                        <div class="fw-bold fs-5">-{{ detail.score_breakdown.late_deduction + detail.score_breakdown.early_deduction }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="rounded p-3 text-center" :class="scoreBgClass(detail.system_score)">
                                        <div class="text-muted fs-8 fw-semibold mb-1">NILAI SISTEM</div>
                                        <div class="fw-bold fs-5">{{ detail.system_score }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-muted fs-8 mb-5">
                                {{ detail.score_breakdown.total_tasks_in_period }} tugas dalam periode ini,
                                masing-masing bobot {{ detail.score_breakdown.weight_per_task }} poin.
                                Tanpa keterangan (info saja, tidak memotong nilai): {{ detail.unexcused_count }}x
                            </div>

                            <!-- Daftar tugas -->
                            <div class="mb-5">
                                <div class="fw-semibold fs-7 mb-2">
                                    Daftar Tugas ({{ detail.task_summary.completed }}/{{ detail.task_summary.total_assigned }} selesai)
                                </div>
                                <div v-if="!detail.task_list.length" class="text-muted fs-7">Belum ada tugas yang diberikan.</div>
                                <div v-else class="table-responsive">
                                    <table class="table table-sm table-row-dashed align-middle fs-8">
                                        <thead>
                                            <tr class="text-muted fw-bold text-uppercase">
                                                <th>Judul</th>
                                                <th>Diberikan</th>
                                                <th>Deadline</th>
                                                <th>Dikumpulkan</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="t in detail.task_list" :key="t.id">
                                                <td>{{ t.title }}</td>
                                                <td>{{ formatDate(t.given_at) }}</td>
                                                <td>{{ t.due_date ? formatDate(t.due_date) : '-' }}</td>
                                                <td>{{ t.submitted_at ? formatDateTime(t.submitted_at) : '-' }}</td>
                                                <td>
                                                    <span class="badge" :class="taskStatusBadge[t.status] ?? 'badge-light-secondary'">
                                                        {{ taskStatusLabel[t.status] ?? t.status }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Riwayat harian -->
                            <div class="mb-5">
                                <div class="fw-semibold fs-7 mb-2">Riwayat Harian (terbaru di atas)</div>
                                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                    <table class="table table-sm table-row-dashed align-middle fs-8">
                                        <thead>
                                            <tr class="text-muted fw-bold text-uppercase">
                                                <th>Tanggal</th>
                                                <th>Masuk</th>
                                                <th>Pulang</th>
                                                <th class="text-center">Nilai Kumulatif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="h in detail.daily_history" :key="h.date">
                                                <td>{{ formatDate(h.date) }}</td>
                                                <td>
                                                    <span v-if="h.check_in_time">
                                                        {{ h.check_in_time }}
                                                        <span v-if="h.is_late" class="text-danger">(telat)</span>
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td>
                                                    <span v-if="h.check_out_time">
                                                        {{ h.check_out_time }}
                                                        <span v-if="h.is_early_leave" class="text-danger">(cepat)</span>
                                                    </span>
                                                    <span v-else class="text-muted">-</span>
                                                </td>
                                                <td class="text-center fw-bold">{{ h.cumulative_score }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>

                        <!-- Input nilai akhir manual (selalu ada, tidak tergantung periode) -->
                        <div class="border rounded p-3 bg-light-primary bg-opacity-25">
                            <div class="fw-semibold fs-7 mb-1">Nilai Akhir (Keputusan Admin)</div>
                            <div v-if="detail.assessment" class="text-muted fs-8 mb-3">
                                Terakhir diisi: {{ formatDateTime(detail.assessment.updated_at) }}
                            </div>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fs-8 fw-semibold">Nilai (0-100)</label>
                                    <input v-model.number="scoreForm.score" type="number" min="0" max="100" class="form-control form-control-sm" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-8 fw-semibold">Catatan (opsional)</label>
                                    <input v-model="scoreForm.note" type="text" class="form-control form-control-sm" placeholder="Alasan / catatan penilaian..." />
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-sm btn-primary w-100" :disabled="savingScore" @click="submitScore">
                                        <span v-if="savingScore" class="spinner-border spinner-border-sm me-2"></span>
                                        Simpan Nilai
                                    </button>
                                </div>
                                <div v-if="scoreFormMsg" class="col-md-12">
                                    <div class="alert alert-danger py-2 fs-7 mb-0">{{ scoreFormMsg }}</div>
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
    has_period: boolean
    attendance: { check_in_time: string | null; check_out_time: string | null; is_late: boolean; late_minutes: number; is_early_leave: boolean; early_minutes: number }
    task_summary: { total_assigned: number; completed: number; overdue_incomplete: number }
    system_score: number | null
    assessment: { score: number; note: string | null; updated_at: string } | null
}

const interns = ref<InternRow[]>([])
const loading = ref(false)
const selectedDate = ref('')

const selectedUser = ref<{ id: number; name: string } | null>(null)
const detail = ref<any>(null)
const detailLoading = ref(false)

const scoreForm = ref({
    score: 0,
    note: '',
})
const scoreFormMsg = ref('')
const savingScore = ref(false)

const taskStatusLabel: Record<string, string> = {
    belum: 'Belum Dikerjakan',
    sedang: 'Sedang Dikerjakan',
    submitted: 'Menunggu Review',
    revisi: 'Perlu Revisi',
    selesai: 'Selesai',
    ditolak: 'Ditolak',
}

const taskStatusBadge: Record<string, string> = {
    belum: 'badge-light-warning',
    sedang: 'badge-light-primary',
    submitted: 'badge-light-info',
    revisi: 'badge-light-danger',
    selesai: 'badge-light-success',
    ditolak: 'badge-light-danger',
}

function toDateInput(d: Date) {
    return d.toISOString().slice(0, 10)
}

function setToday() {
    selectedDate.value = toDateInput(new Date())
    loadInterns()
}

function formatDate(dateStr: string) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDateTime(dateStr: string) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function scoreBadgeClass(score: number | null) {
    if (score === null || score === undefined) return 'badge-light-secondary'
    if (score >= 85) return 'badge-light-success'
    if (score >= 70) return 'badge-light-warning'
    return 'badge-light-danger'
}

function scoreBgClass(score: number | null) {
    if (score === null || score === undefined) return 'bg-light'
    if (score >= 85) return 'bg-light-success'
    if (score >= 70) return 'bg-light-warning'
    return 'bg-light-danger'
}

async function loadInterns() {
    loading.value = true
    try {
        const res = await axios.get('/admin/assessments', { params: { date: selectedDate.value } })
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
    scoreFormMsg.value = ''
    await loadDetail()
}

async function loadDetail() {
    if (!selectedUser.value) return
    detailLoading.value = true
    try {
        const res = await axios.get(`/admin/assessments/${selectedUser.value.id}`, { params: { date: selectedDate.value } })
        detail.value = res.data?.data ?? null
        scoreForm.value = {
            score: detail.value?.assessment?.score ?? 0,
            note: detail.value?.assessment?.note ?? '',
        }
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
    scoreFormMsg.value = ''
}

async function submitScore() {
    if (!selectedUser.value) return
    if (scoreForm.value.score < 0 || scoreForm.value.score > 100) {
        scoreFormMsg.value = 'Nilai harus di antara 0 - 100'
        return
    }

    savingScore.value = true
    scoreFormMsg.value = ''
    try {
        await axios.post(`/admin/assessments/${selectedUser.value.id}/score`, scoreForm.value)
        toast.success('Nilai akhir berhasil disimpan')
        await loadDetail()
        await loadInterns()
    } catch (e: any) {
        scoreFormMsg.value = e.response?.data?.message ?? 'Gagal menyimpan nilai akhir'
    } finally {
        savingScore.value = false
    }
}

onMounted(() => {
    setToday()
})
</script>