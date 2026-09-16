<script setup lang="ts">
import { ref, onMounted } from "vue";
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

interface Holiday {
    id: number;
    date: string;
    label: string;
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
const holidays = ref<Holiday[]>([]);
const loading = ref(false);

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

// ─── Form tambah tanggal merah ─────────────────────────────────────────────
const holidayForm = ref({ date: "", label: "" });
const savingHoliday = ref(false);
const holidayErrorMsg = ref("");
const deletingHolidayId = ref<number | null>(null);

function toHM(time: string) {
    // Backend kirim "08:00:00", input type=time butuh "08:00"
    return time ? time.slice(0, 5) : "";
}

async function loadSchedule() {
    loading.value = true;
    try {
        const res = await axios.get("/admin/work-schedule");
        schedules.value = res.data?.data ?? [];
        holidays.value = res.data?.holidays ?? [];
    } catch (e: any) {
        toast.error(e?.response?.data?.message || "Gagal memuat jadwal kerja.");
    } finally {
        loading.value = false;
    }
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
        await axios.put(`/admin/work-schedule/${editingDay.value}`, editForm.value);
        toast.success("Jadwal berhasil disimpan");
        showEditModal.value = false;
        await loadSchedule();
    } catch (e: any) {
        editErrorMsg.value = e?.response?.data?.message || "Gagal menyimpan jadwal kerja.";
    } finally {
        savingSchedule.value = false;
    }
}

async function submitHoliday() {
    if (!holidayForm.value.date || !holidayForm.value.label.trim()) {
        holidayErrorMsg.value = "Tanggal dan nama libur wajib diisi";
        return;
    }
    savingHoliday.value = true;
    holidayErrorMsg.value = "";
    try {
        await axios.post("/admin/work-schedule/holidays", holidayForm.value);
        toast.success("Tanggal merah berhasil ditambahkan");
        holidayForm.value = { date: "", label: "" };
        await loadSchedule();
    } catch (e: any) {
        holidayErrorMsg.value = e?.response?.data?.message || "Gagal menambahkan tanggal merah.";
    } finally {
        savingHoliday.value = false;
    }
}

async function deleteHoliday(holiday: Holiday) {
    if (!confirm(`Hapus tanggal merah "${holiday.label}"?`)) return;
    deletingHolidayId.value = holiday.id;
    try {
        await axios.delete(`/admin/work-schedule/holidays/${holiday.id}`);
        toast.success("Tanggal merah berhasil dihapus");
        await loadSchedule();
    } catch (e: any) {
        toast.error(e?.response?.data?.message || "Gagal menghapus tanggal merah.");
    } finally {
        deletingHolidayId.value = null;
    }
}

function formatDate(dateStr: string) {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", { day: "2-digit", month: "long", year: "numeric" });
}

onMounted(loadSchedule);
</script>

<template>
    <div class="row g-5">
        <div class="col-12">
            <!-- ══ JADWAL MINGGUAN ══ -->
            <div class="card mb-5">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Pengaturan Hari & Jam Kerja</h2>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div v-if="loading" class="d-flex justify-content-center py-10">
                        <span class="spinner-border text-primary"></span>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-4">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Hari</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Min. Masuk</th>
                                    <th>Max. Pulang</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="schedule in schedules" :key="schedule.day">
                                    <td class="fw-bold text-uppercase">{{ dayLabels[schedule.day] }}</td>
                                    <td>{{ toHM(schedule.start_time) }}</td>
                                    <td>{{ toHM(schedule.end_time) }}</td>
                                    <td class="fst-italic text-muted">{{ toHM(schedule.min_check_in_time) }}</td>
                                    <td>{{ toHM(schedule.max_check_out_time) }}</td>
                                    <td>
                                        <span class="badge" :class="schedule.is_working_day ? 'badge-light-success' : 'badge-light-danger'">
                                            {{ schedule.is_working_day ? "HARI KERJA" : "LIBUR" }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-icon btn-light-warning" @click="openEdit(schedule)">
                                            <i class="bi bi-pencil-fill fs-6"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══ TANGGAL MERAH / HARI LIBUR KHUSUS ══ -->
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Tanggal Merah / Hari Libur Khusus</h2>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div class="text-muted fs-8 mb-4">
                        Tanggal yang ditambahkan di sini otomatis jadi hari libur, terlepas dari hari apa pun
                        itu jatuhnya. Tanggal merah tetap (Tahun Baru, Kemerdekaan, dst) sudah otomatis
                        tersedia — tambahkan sendiri tanggal merah yang geser tiap tahun (Lebaran, Nyepi,
                        cuti bersama, dll).
                    </div>

                    <div class="row g-3 mb-6">
                        <div class="col-md-4">
                            <input v-model="holidayForm.date" type="date" class="form-control form-control-solid" />
                        </div>
                        <div class="col-md-5">
                            <input v-model="holidayForm.label" type="text" class="form-control form-control-solid" placeholder="Nama hari libur (misal: Hari Raya Idul Fitri)" />
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" :disabled="savingHoliday" @click="submitHoliday">
                                <span v-if="savingHoliday" class="spinner-border spinner-border-sm me-2"></span>
                                Tambah
                            </button>
                        </div>
                        <div v-if="holidayErrorMsg" class="col-12">
                            <div class="alert alert-danger py-2 fs-7 mb-0">{{ holidayErrorMsg }}</div>
                        </div>
                    </div>

                    <div v-if="!holidays.length" class="text-muted fs-7 text-center py-5">
                        Belum ada tanggal merah yang ditambahkan.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="h in holidays" :key="h.id">
                                    <td>{{ formatDate(h.date) }}</td>
                                    <td>{{ h.label }}</td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-sm btn-icon btn-light-danger"
                                            :disabled="deletingHolidayId === h.id"
                                            @click="deleteHoliday(h)"
                                        >
                                            <span v-if="deletingHolidayId === h.id" class="spinner-border spinner-border-sm"></span>
                                            <i v-else class="bi bi-trash-fill fs-6"></i>
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

    <!-- ══ MODAL EDIT JAM KERJA ══ -->
    <div v-if="showEditModal" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Jam Kerja: {{ dayLabels[editingDay] }}</h5>
                    <button class="btn btn-sm btn-icon btn-light" @click="closeEdit">✕</button>
                </div>
                <div class="modal-body">
                    <div class="border border-danger rounded p-3 mb-4">
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
                            <label class="form-label fw-semibold">Minimal Jam Masuk</label>
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