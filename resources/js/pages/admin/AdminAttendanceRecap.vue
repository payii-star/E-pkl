<template>
    <div class="d-flex flex-column gap-5">

        <!-- ══ STRIP PESERTA MAGANG (horizontal, bisa di-scroll) ══ -->
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h2 class="fw-bold">Daftar Peserta Magang</h2>
                </div>
                <div class="card-toolbar">
                    <div class="position-relative w-200px">
                        <KTIcon icon-name="magnifier"
                            icon-class="fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-3" />
                        <input v-model="search" type="text" class="form-control form-control-sm ps-10"
                            placeholder="Cari nama..." />
                    </div>
                </div>
            </div>
            <div class="card-body pt-2">
                <div v-if="loadingInterns" class="text-center py-10">
                    <div class="spinner-border text-primary"></div>
                </div>
                <div v-else-if="filteredInterns.length === 0" class="text-center text-muted py-8">
                    Tidak ada data peserta magang
                </div>
                <div v-else class="intern-strip">
                    <div v-for="intern in filteredInterns" :key="intern.intern_id"
                        class="intern-chip d-flex flex-column align-items-center gap-2 p-3 rounded cursor-pointer flex-shrink-0"
                        :class="{ 'intern-chip--active': selected?.intern_id === intern.intern_id }"
                        @click="selectIntern(intern)">

                        <div class="symbol symbol-50px">
                            <img v-if="intern.photo" :src="intern.photo" alt="foto" class="rounded" />
                            <span v-else class="symbol-label bg-light-primary text-primary fw-bold fs-5">
                                {{ intern.name?.charAt(0)?.toUpperCase() }}
                            </span>
                        </div>

                        <div class="text-center" style="width: 100px">
                            <div class="fw-bold text-gray-800 text-truncate fs-8">{{ intern.name }}</div>
                            <span class="badge badge-light-success mt-1">{{ intern.total_hadir_periode }}x</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ PLACEHOLDER JIKA BELUM PILIH ══ -->
        <div v-if="!selected" class="card">
            <div class="card-body d-flex align-items-center justify-content-center py-15">
                <div class="text-center text-muted">
                    <KTIcon icon-name="calendar" icon-class="fs-3x mb-3 text-gray-300" />
                    <div class="fs-5 fw-semibold">Pilih peserta magang</div>
                    <div class="fs-7 mt-1">Klik salah satu nama di atas untuk melihat rekap absensi</div>
                </div>
            </div>
        </div>

        <template v-else>
            <!-- ══ PROFIL + FILTER PERIODE ══ -->
            <div class="card">
                <div class="card-body py-5">
                    <div class="d-flex align-items-center gap-4 flex-wrap">
                        <div class="symbol symbol-55px">
                            <img v-if="selected.photo" :src="selected.photo" class="rounded" />
                            <span v-else class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                                {{ selected.name?.charAt(0)?.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex-fill min-w-150px">
                            <div class="fw-bold fs-4 text-gray-800">{{ selected.name }}</div>
                            <div class="text-muted fs-7">{{ selected.institusi_asal ?? '-' }} · {{ selected.posisi ?? '-' }}</div>
                        </div>

                        <div class="flex-shrink-0">
                            <label class="fs-8 text-muted mb-1 d-block">Tampilan</label>
                            <select class="form-select form-select-sm" v-model="period" @change="onPeriodChange">
                                <option value="week">Mingguan</option>
                                <option value="month">Bulanan</option>
                            </select>
                        </div>

                        <div class="flex-shrink-0">
                            <label class="fs-8 text-muted mb-1 d-block">
                                {{ period === 'week' ? 'Pilih Tanggal' : 'Pilih Bulan' }}
                            </label>
                            <input
                                v-if="period === 'month'"
                                type="month"
                                class="form-control form-control-sm"
                                v-model="selectedMonth"
                            />
                            <input
                                v-else
                                type="date"
                                class="form-control form-control-sm"
                                v-model="selectedWeekDate"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading rekap -->
            <div v-if="loadingRecap" class="card">
                <div class="card-body text-center py-15">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>

            <template v-else-if="recap">
                <!-- ══ RINGKASAN — bar horizontal ikon + angka ══ -->
                <div class="card">
                    <div class="card-body py-5">
                        <div class="d-flex flex-wrap gap-5">
                            <div class="d-flex align-items-center gap-3 flex-fill">
                                <div class="symbol symbol-45px">
                                    <span class="symbol-label bg-light">
                                        <KTIcon icon-name="calendar" icon-class="fs-2 text-gray-600" />
                                    </span>
                                </div>
                                <div>
                                    <div class="fs-3 fw-bold text-gray-800 lh-1">{{ recap.summary.total_hari_kerja }}</div>
                                    <div class="text-muted fs-8">Hari Kerja</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-fill">
                                <div class="symbol symbol-45px">
                                    <span class="symbol-label bg-light-success">
                                        <KTIcon icon-name="check" icon-class="fs-2 text-success" />
                                    </span>
                                </div>
                                <div>
                                    <div class="fs-3 fw-bold text-success lh-1">{{ recap.summary.total_hadir }}</div>
                                    <div class="text-muted fs-8">Hadir</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-fill">
                                <div class="symbol symbol-45px">
                                    <span class="symbol-label bg-light-danger">
                                        <KTIcon icon-name="cross" icon-class="fs-2 text-danger" />
                                    </span>
                                </div>
                                <div>
                                    <div class="fs-3 fw-bold text-danger lh-1">{{ recap.summary.total_tidak_hadir }}</div>
                                    <div class="text-muted fs-8">Tidak Hadir</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-fill">
                                <div class="symbol symbol-45px">
                                    <span class="symbol-label bg-light-primary">
                                        <KTIcon icon-name="chart-simple" icon-class="fs-2 text-primary" />
                                    </span>
                                </div>
                                <div>
                                    <div class="fs-3 fw-bold text-primary lh-1">{{ recap.summary.persentase_kehadiran }}%</div>
                                    <div class="text-muted fs-8">Kehadiran</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ TABEL HARIAN ══ -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="fw-bold mb-0">Rincian Harian — {{ periodLabel }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table table-row-bordered align-middle">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="day in recap.days" :key="day.date"
                                        :class="{ 'bg-light-secondary': day.is_weekend }">
                                        <td>{{ formatDayLabel(day.date) }}</td>
                                        <td>{{ formatDayName(day.date) }}</td>
                                        <td>{{ day.check_in_time ?? '-' }}</td>
                                        <td>{{ day.check_out_time ?? '-' }}</td>
                                        <td>
                                            <span class="badge" :class="statusBadgeClass(day.status)">
                                                {{ statusLabel(day.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/libs/axios'

// ─── State ────────────────────────────────────────────────────────────────────
const interns = ref<any[]>([])
const selected = ref<any>(null)
const search = ref('')
const loadingInterns = ref(false)

const recap = ref<any>(null)
const loadingRecap = ref(false)

const period = ref<'week' | 'month'>('month')
const selectedMonth = ref(currentMonthValue())
const selectedWeekDate = ref(currentDateValue())

function currentMonthValue() {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
}

function currentDateValue() {
    const now = new Date()
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`
}

// ─── Computed ─────────────────────────────────────────────────────────────────
const filteredInterns = computed(() =>
    interns.value.filter(i =>
        i.name?.toLowerCase().includes(search.value.toLowerCase()) ||
        i.institusi_asal?.toLowerCase().includes(search.value.toLowerCase())
    )
)

const periodParams = computed(() =>
    period.value === 'week'
        ? { period: 'week', date: selectedWeekDate.value }
        : { period: 'month', month: selectedMonth.value }
)

const periodLabel = computed(() => {
    if (!recap.value?.range) return '-'
    const start = new Date(recap.value.range.start + 'T00:00:00')
    const end = new Date(recap.value.range.end + 'T00:00:00')

    if (period.value === 'week') {
        const startLabel = start.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
        const endLabel = end.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
        return `${startLabel} - ${endLabel}`
    }

    return start.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })
})

// ─── Load data ────────────────────────────────────────────────────────────────
async function loadInterns() {
    loadingInterns.value = true
    try {
        const res = await axios.get('/admin/attendance/interns', { params: periodParams.value })
        interns.value = res.data.data ?? []
    } catch (e: any) {
        console.error('Gagal load peserta magang:', e)
    } finally {
        loadingInterns.value = false
    }
}

async function loadRecap() {
    if (!selected.value) return
    loadingRecap.value = true
    recap.value = null
    try {
        const res = await axios.get(`/admin/attendance/${selected.value.intern_id}`, { params: periodParams.value })
        recap.value = res.data
    } catch (e: any) {
        console.error('Gagal load rekap absensi:', e)
    } finally {
        loadingRecap.value = false
    }
}

function selectIntern(intern: any) {
    selected.value = intern
    loadRecap()
}

function onPeriodChange() {
    loadInterns()
    if (selected.value) loadRecap()
}

// Reload saat bulan/tanggal-minggu diganti
watch([selectedMonth, selectedWeekDate], () => {
    if (selected.value) loadRecap()
    loadInterns()
})

// ─── Formatting helpers ─────────────────────────────────────────────────────────
function formatDayLabel(dateStr: string) {
    const d = new Date(dateStr + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDayName(dateStr: string) {
    const d = new Date(dateStr + 'T00:00:00')
    return d.toLocaleDateString('id-ID', { weekday: 'long' })
}

function statusLabel(status: string) {
    const map: Record<string, string> = {
        hadir: 'Hadir',
        hadir_belum_checkout: 'Belum Checkout',
        tidak_hadir: 'Tidak Hadir',
        libur: 'Libur',
        akan_datang: '-',
    }
    return map[status] ?? status
}

function statusBadgeClass(status: string) {
    const map: Record<string, string> = {
        hadir: 'badge-light-success',
        hadir_belum_checkout: 'badge-light-warning',
        tidak_hadir: 'badge-light-danger',
        libur: 'badge-light-secondary',
        akan_datang: 'badge-light',
    }
    return map[status] ?? 'badge-light'
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    await loadInterns()
})
</script>

<style scoped>
.intern-strip {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 4px;
}
.intern-chip {
    border: 1.5px solid #f1f1f2;
    transition: all .15s;
    cursor: pointer;
    min-width: 110px;
}
.intern-chip:hover     { background: #f9f9f9; border-color: #d9d9e0; }
.intern-chip--active   { background: #eef6ff; border-color: #009ef7 !important; }
</style>