<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";

interface DaySchedule {
    id?: number;
    day: string;
    is_working_day: boolean;
    start_time: string;
    end_time: string;
    min_check_in_time: string;
    max_check_out_time: string;
}

const dayLabels: Record<string, string> = {
    monday: "Senin",
    tuesday: "Selasa",
    wednesday: "Rabu",
    thursday: "Kamis",
    friday: "Jumat",
    saturday: "Sabtu",
    sunday: "Minggu",
};

const schedules = ref<DaySchedule[]>([]);
const interns = ref<any[]>([]);
const selected = ref<any>(null);
const search = ref("");
const loading = ref(false);
const loadingInterns = ref(false);
const period = ref<"week" | "month">("month");
const selectedMonth = ref(currentMonthValue());
const selectedWeekDate = ref(currentDateValue());

// ─── Modal edit jadwal harian ──────────────────────────────────────────────
const showEditModal = ref(false);
const editingDay = ref<string>("");
const editForm = ref({
    is_working_day: true,
    start_time: "08:00",
    end_time: "16:00",
    min_check_in_time: "00:00",
    max_check_out_time: "23:59",
});
const savingSchedule = ref(false);
const editErrorMsg = ref("");

const filteredInterns = computed(() => interns.value.filter((intern) =>
    intern.name?.toLowerCase().includes(search.value.toLowerCase()) ||
    intern.email?.toLowerCase().includes(search.value.toLowerCase())
));

function currentMonthValue() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
}

function currentDateValue() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
}

function onPeriodChange() {
    if (period.value === "week") selectedWeekDate.value = currentDateValue();
}

function dateForDay(day: string) {
    const selectedDate = new Date(`${selectedWeekDate.value}T00:00:00`);
    const mondayOffset = selectedDate.getDay() === 0 ? -6 : 1 - selectedDate.getDay();
    const dayIndex = Object.keys(dayLabels).indexOf(day);
    selectedDate.setDate(selectedDate.getDate() + mondayOffset + dayIndex);

    return selectedDate.toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

const tableRows = computed(() => {
    if (period.value === "week") {
        return schedules.value.map((schedule) => ({
            date: dateForDay(schedule.day),
            day: dayLabels[schedule.day],
            schedule,
        }));
    }

    const [year, month] = selectedMonth.value.split("-").map(Number);
    const daysInMonth = new Date(year, month, 0).getDate();
    const dayKeys = Object.keys(dayLabels);

    return Array.from({ length: daysInMonth }, (_, index) => {
        const date = new Date(year, month - 1, index + 1);
        const dayKey = dayKeys[date.getDay() === 0 ? 6 : date.getDay() - 1];

        return {
            date: date.toLocaleDateString("id-ID", {
                day: "2-digit",
                month: "short",
                year: "numeric",
            }),
            day: dayLabels[dayKey],
            schedule: schedules.value.find((schedule) => schedule.day === dayKey),
        };
    }).filter((row) => row.schedule);
});

function toHM(time: string) {
    // Backend kirim "08:00:00", input type=time butuh "08:00"
    return time ? time.slice(0, 5) : "";
}

async function loadInterns() {
    loadingInterns.value = true;
    try {
        const res = await axios.get("/admin/intern-periods");
        interns.value = res.data?.data ?? [];
    } catch (e: any) {
        toast.error(e?.response?.data?.message || "Gagal memuat peserta magang.");
    } finally {
        loadingInterns.value = false;
    }
}

async function loadSchedule() {
    if (!selected.value) return;
    loading.value = true;
    try {
        const res = await axios.get(`/admin/work-schedule/${selected.value.id}`);
        schedules.value = res.data?.data ?? [];
    } catch (e: any) {
        toast.error(e?.response?.data?.message || "Gagal memuat jadwal kerja.");
    } finally {
        loading.value = false;
    }
}

function selectIntern(intern: any) {
    selected.value = intern;
    loadSchedule();
}

function openEdit(schedule: DaySchedule) {
    editingDay.value = schedule.day;
    editForm.value = {
        is_working_day: schedule.is_working_day,
        start_time: toHM(schedule.start_time),
        end_time: toHM(schedule.end_time),
        min_check_in_time: toHM(schedule.min_check_in_time),
        max_check_out_time: toHM(schedule.max_check_out_time),
    };
    editErrorMsg.value = "";
    showEditModal.value = true;
}

function closeEdit() {
    showEditModal.value = false;
}

async function saveSchedule() {
    savingSchedule.value = true;
    editErrorMsg.value = "";
    try {
        await axios.put(`/admin/work-schedule/${selected.value.id}/${editingDay.value}`, editForm.value);
        toast.success("Jadwal berhasil disimpan");
        showEditModal.value = false;
        await loadSchedule();
    } catch (e: any) {
        editErrorMsg.value = e?.response?.data?.message || "Gagal menyimpan jadwal kerja.";
    } finally {
        savingSchedule.value = false;
    }
}

onMounted(loadInterns);
</script>

<template>
    <div class="d-flex flex-column gap-5">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title"><h2 class="fw-bold">Daftar Peserta Magang</h2></div>
                <div class="card-toolbar">
                    <input v-model="search" type="text" class="form-control form-control-sm w-200px" placeholder="Cari nama..." />
                </div>
            </div>
            <div class="card-body pt-2">
                <div v-if="loadingInterns" class="text-center py-10"><span class="spinner-border text-primary"></span></div>
                <div v-else-if="!filteredInterns.length" class="text-center text-muted py-10">Belum ada peserta magang.</div>
                <div v-else class="intern-strip">
                    <div v-for="intern in filteredInterns" :key="intern.id"
                        class="intern-chip d-flex flex-column align-items-center gap-2 p-3 rounded cursor-pointer flex-shrink-0"
                        :class="{ 'intern-chip--active': selected?.id === intern.id }" @click="selectIntern(intern)">
                        <div class="symbol symbol-50px">
                            <img v-if="intern.photo" :src="intern.photo" alt="foto" class="rounded" />
                            <span v-else class="symbol-label bg-light-primary text-primary fw-bold fs-5">{{ intern.name?.charAt(0)?.toUpperCase() }}</span>
                        </div>
                        <div class="fw-bold text-gray-800 text-truncate fs-8" style="width: 100px; text-align: center">{{ intern.name }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!selected" class="card">
            <div class="card-body text-center text-muted py-15">
                <div class="fs-5 fw-semibold">Pilih peserta magang</div>
                <div class="fs-7 mt-1">Klik salah satu nama di atas untuk mengatur jam kerja</div>
            </div>
        </div>

        <template v-else>
        <div class="card">
            <div class="card-body py-5">
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="symbol symbol-55px">
                        <img v-if="selected.photo" :src="selected.photo" alt="foto" class="rounded" />
                        <span v-else class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                            {{ selected.name?.charAt(0)?.toUpperCase() }}
                        </span>
                    </div>
                    <div class="flex-fill min-w-150px">
                        <div class="fw-bold fs-4 text-gray-800">{{ selected.name }}</div>
                        <div class="text-muted fs-7">{{ selected.email ?? "-" }}</div>
                    </div>
                    <div class="flex-shrink-0">
                        <label class="fs-8 text-muted mb-1 d-block">Tampilan</label>
                        <select class="form-select form-select-sm" v-model="period" @change="onPeriodChange">
                            <option value="week">Mingguan</option>
                            <option value="month">Bulanan</option>
                        </select>
                    </div>
                    <div v-if="period === 'month'" class="flex-shrink-0">
                        <label class="fs-8 text-muted mb-1 d-block">Pilih Bulan</label>
                        <input v-model="selectedMonth" type="month" class="form-control form-control-sm" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h2 class="fw-bold mb-0">Pengaturan Hari & Jam Kerja</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                <div v-if="loading" class="d-flex justify-content-center py-10"><span class="spinner-border text-primary"></span></div>
                <div v-else class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tanggal</th>
                                    <th>Hari</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Toleransi Terlambat</th>
                                    <th>Max. Pulang</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in tableRows" :key="`${row.date}-${row.day}`">
                                    <td>{{ row.date }}</td>
                                    <td class="fw-bold text-uppercase">{{ row.day }}</td>
                                    <td>{{ toHM(row.schedule?.start_time ?? "") }}</td>
                                    <td>{{ toHM(row.schedule?.end_time ?? "") }}</td>
                                    <td class="fst-italic text-muted">{{ toHM(row.schedule?.min_check_in_time ?? "") }}</td>
                                    <td>{{ toHM(row.schedule?.max_check_out_time ?? "") }}</td>
                                    <td>
                                        <span class="badge" :class="row.schedule?.is_working_day ? 'badge-light-success' : 'badge-light-danger'">
                                            {{ row.schedule?.is_working_day ? "HARI KERJA" : "LIBUR" }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-icon btn-light-warning" @click="openEdit(row.schedule!)">
                                            <i class="bi bi-pencil-fill fs-6"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
        </template>
    </div>

    <!-- ══ MODAL EDIT JAM KERJA ══ -->
    <div v-if="showEditModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Jam Kerja: {{ dayLabels[editingDay] }}</h5>
                    <button class="btn btn-sm btn-icon btn-light" @click="closeEdit">✕</button>
                </div>
                <div class="modal-body">
                    <div class="border rounded p-3 mb-4">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Jam Masuk</label>
                                <input v-model="editForm.start_time" type="time" class="form-control" />
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Jam Pulang</label>
                                <input v-model="editForm.end_time" type="time" class="form-control" />
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Toleransi Terlambat</label>
                            <input v-model="editForm.min_check_in_time" type="time" class="form-control" />
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Maksimal Jam Pulang</label>
                            <input v-model="editForm.max_check_out_time" type="time" class="form-control" />
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold d-block">Status Hari</label>
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn flex-fill"
                                :class="editForm.is_working_day ? 'btn-success' : 'btn-light'"
                                @click="editForm.is_working_day = true"
                            >
                                HARI KERJA
                            </button>
                            <button
                                type="button"
                                class="btn flex-fill"
                                :class="!editForm.is_working_day ? 'btn-danger' : 'btn-light'"
                                @click="editForm.is_working_day = false"
                            >
                                LIBUR
                            </button>
                        </div>
                    </div>

                    <div v-if="editErrorMsg" class="alert alert-danger py-2 fs-7 mt-3 mb-0">{{ editErrorMsg }}</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="closeEdit">Batal</button>
                    <button class="btn btn-primary" :disabled="savingSchedule" @click="saveSchedule">
                        <span v-if="savingSchedule" class="spinner-border spinner-border-sm me-2"></span>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.intern-strip {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 4px;
}

.intern-chip {
    border: 1.5px solid #f1f1f2;
    min-width: 110px;
    transition: all .15s;
}

.intern-chip:hover {
    background: #f9f9f9;
    border-color: #d9d9e0;
}

.intern-chip--active {
    background: #eef6ff;
    border-color: #009ef7 !important;
}
</style>