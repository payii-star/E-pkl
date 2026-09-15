<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Penilaian</h2>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <template v-else-if="data">
                        <div class="fw-bold fs-5 mb-4">Tahun {{ periodYear }}</div>

                        <div class="table-responsive">
                            <table class="table table-row-bordered align-middle mb-0">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase table-head">
                                        <th style="width:50px">No.</th>
                                        <th>Perusahaan</th>
                                        <th style="width:180px">Periode PKL</th>
                                        <th style="width:180px">Nama Siswa</th>
                                        <th style="width:140px">NIS</th>
                                        <th style="width:200px">Asal Sekolah</th>
                                        <th style="width:190px">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1.</td>
                                        <td>
                                            <div class="fw-semibold text-gray-800">{{ company.name }}</div>
                                            <div v-if="company.address" class="text-muted fs-8 fst-italic">
                                                {{ company.address }}
                                            </div>
                                        </td>
                                        <td class="fs-7">
                                            <span v-if="data.has_period">
                                                {{ formatDate(data.period.start) }} - {{ formatDate(data.period.end) }}
                                            </span>
                                            <span v-else class="text-muted fst-italic">Belum diatur</span>
                                        </td>
                                        <td class="fw-semibold text-gray-800">{{ data.user.name }}</td>
                                        <td class="fs-7">{{ data.user.nim_nis || '-' }}</td>
                                        <td class="fs-7">{{ data.user.asal_instansi || '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-column align-items-start gap-2">
                                                <span class="badge" :class="periodStatus.badgeClass">
                                                    {{ periodStatus.label }}
                                                </span>

                                                <!-- Belum dinilai admin -->
                                                <span v-if="!data.assessment" class="text-muted fs-8 fst-italic">
                                                    ~ Belum dinilai ~
                                                </span>

                                                <!-- Sudah dinilai admin -> tombol Nilai -->
                                                <button
                                                    v-else
                                                    class="btn btn-sm btn-primary py-1 px-4"
                                                    @click="showDetail = true"
                                                >
                                                    Nilai
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <div v-else class="text-center text-muted py-10">
                        Data penilaian belum tersedia.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ CARD DETAIL NILAI ══ -->
    <div v-if="showDetail && data" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold mb-0">Detail Penilaian Magang</h5>
                    <button class="btn btn-sm btn-icon btn-light" @click="showDetail = false">✕</button>
                </div>

                <div class="modal-body">
                    <!-- Identitas -->
                    <div class="mb-5">
                        <div class="fs-7"><span class="text-muted">Nama Siswa</span> : <b>{{ data.user.name }}</b></div>
                        <div class="fs-7"><span class="text-muted">Nomor Induk Siswa (NIS)</span> : <b>{{ data.user.nim_nis || '-' }}</b></div>
                        <div class="fs-7"><span class="text-muted">Asal Sekolah</span> : <b>{{ data.user.asal_instansi || '-' }}</b></div>
                        <div class="fs-7"><span class="text-muted">Perusahaan</span> : <b>{{ company.name }}</b></div>
                        <div v-if="data.has_period" class="fs-7">
                            <span class="text-muted">Periode Magang</span> : <b>{{ formatDate(data.period.start) }} — {{ formatDate(data.period.end) }}</b>
                        </div>
                    </div>

                    <!-- Nilai -->
                    <div class="row g-3 mb-6">
                        <div class="col-6">
                            <div class="rounded p-4 text-center bg-light">
                                <div class="text-muted fs-8 fw-semibold mb-1">NILAI SISTEM (OTOMATIS)</div>
                                <div class="fw-bold fs-2x">{{ data.has_period ? data.system_score : '-' }}</div>
                                <div v-if="!data.has_period" class="text-muted fs-8">Periode magang belum diatur</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="rounded p-4 text-center" :class="scoreBgClass(data.assessment.score)">
                                <div class="text-muted fs-8 fw-semibold mb-1">NILAI AKHIR (ADMIN)</div>
                                <div class="fw-bold fs-2x">{{ data.assessment.score }}</div>
                                <div v-if="data.assessment.note" class="fs-8 fst-italic mt-1">"{{ data.assessment.note }}"</div>
                            </div>
                        </div>
                    </div>

                    <!-- Aspek Kehadiran & Kedisiplinan -->
                    <div class="fw-bold fs-6 mb-3">Kehadiran & Kedisiplinan</div>
                    <div class="table-responsive mb-6">
                        <table class="table table-row-bordered align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-8">
                                    <th>No.</th>
                                    <th>Aspek</th>
                                    <th class="text-center">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody v-if="data.has_period">
                                <tr>
                                    <td>1</td>
                                    <td>Terlambat Masuk</td>
                                    <td class="text-center">{{ attendanceCounts.telat }}x</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td>Pulang Lebih Awal</td>
                                    <td class="text-center">{{ attendanceCounts.pulang_cepat }}x</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td>Tanpa Keterangan</td>
                                    <td class="text-center">{{ data.unexcused_count }}x</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr><td colspan="3" class="text-center text-muted">Periode magang belum diatur</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Aspek Tugas -->
                    <div class="fw-bold fs-6 mb-3">Progres Tugas</div>
                    <div class="table-responsive mb-6">
                        <table class="table table-row-bordered align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-8">
                                    <th>No.</th>
                                    <th>Judul Tugas</th>
                                    <th>Diberikan</th>
                                    <th>Deadline</th>
                                    <th>Dikumpulkan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody v-if="data.task_list.length">
                                <tr v-for="(t, i) in data.task_list" :key="t.id">
                                    <td>{{ Number(i) + 1 }}</td>
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
                            <tbody v-else>
                                <tr><td colspan="6" class="text-center text-muted">Belum ada tugas yang diberikan</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Aspek Izin -->
                    <div class="fw-bold fs-6 mb-3">Izin</div>
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle fs-7">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-8">
                                    <th>Jenis</th>
                                    <th class="text-center">Jumlah (Hari)</th>
                                </tr>
                            </thead>
                            <tbody v-if="data.has_period">
                                <tr>
                                    <td>Sakit</td>
                                    <td class="text-center">{{ data.leave_summary.sakit }}</td>
                                </tr>
                                <tr>
                                    <td>Acara Keluarga</td>
                                    <td class="text-center">{{ data.leave_summary.acara_keluarga }}</td>
                                </tr>
                                <tr>
                                    <td>Tanpa Keterangan</td>
                                    <td class="text-center">{{ data.leave_summary.tanpa_keterangan }}</td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr><td colspan="2" class="text-center text-muted">Periode magang belum diatur</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" @click="showDetail = false">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from '@/libs/axios'
import { toast } from 'vue3-toastify'

const data = ref<any>(null)
const loading = ref(false)
const showDetail = ref(false)

// Nama & alamat perusahaan diambil dari Setting (data yang memang sudah ada
// di sistem), bukan di-hardcode.
const company = ref<{ name: string; address: string }>({
    name: '-',
    address: '',
})

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

// Tahun yang ditampilkan sebagai judul tabel — ikut tahun periode magang,
// fallback ke tahun berjalan kalau periode belum diatur.
const periodYear = computed(() => {
    if (data.value?.has_period && data.value.period?.start) {
        return new Date(data.value.period.start).getFullYear()
    }
    return new Date().getFullYear()
})

// Status periode PKL: belum mulai / sedang berjalan / selesai
const periodStatus = computed(() => {
    if (!data.value?.has_period) {
        return { label: 'PERIODE BELUM DIATUR', badgeClass: 'badge-light-secondary' }
    }

    const today = new Date()
    today.setHours(0, 0, 0, 0)
    const start = new Date(data.value.period.start)
    const end = new Date(data.value.period.end)

    if (today < start) {
        return { label: 'BELUM DIMULAI', badgeClass: 'badge-light-warning' }
    }
    if (today > end) {
        return { label: 'SELESAI', badgeClass: 'badge-light-success' }
    }
    return { label: 'SEDANG BERJALAN', badgeClass: 'badge-info' }
})

// Hitung total telat/pulang cepat dari riwayat harian (biar konsisten dgn
// perhitungan nilai sistem yang juga akumulatif dari riwayat harian)
const attendanceCounts = computed(() => {
    if (!data.value?.daily_history) return { telat: 0, pulang_cepat: 0 }
    return {
        telat: data.value.daily_history.filter((h: any) => h.is_late).length,
        pulang_cepat: data.value.daily_history.filter((h: any) => h.is_early_leave).length,
    }
})

function formatDate(dateStr: string) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

function formatDateTime(dateStr: string) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function scoreBgClass(score: number) {
    if (score >= 85) return 'bg-light-success'
    if (score >= 70) return 'bg-light-warning'
    return 'bg-light-danger'
}

async function loadCompany() {
    try {
        const res = await axios.get('/setting')
        const s = res.data?.data ?? res.data ?? {}
        company.value = {
            name: s.app ?? '-',
            address: s.alamat ?? '',
        }
    } catch (e) {
        console.error('Gagal memuat data perusahaan:', e)
    }
}

async function loadData() {
    loading.value = true
    try {
        const res = await axios.get('/assessments/me')
        data.value = res.data?.data ?? null
    } catch (e) {
        console.error('Gagal memuat data nilai:', e)
        toast.error('Gagal memuat data nilai kamu')
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await Promise.all([loadData(), loadCompany()])
})
</script>

<style scoped>
.table-head th {
    background: #009ef7;
    color: #fff;
    vertical-align: middle;
}
.table-head th:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}
.table-head th:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}
</style>