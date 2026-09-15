<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Riwayat Absensi</h2>
            </div>
            <div class="card-toolbar">
                <input
                    type="month"
                    class="form-control form-control-sm"
                    v-model="selectedMonth"
                    style="width: 160px"
                />
            </div>
        </div>
        <div class="card-body pt-2">
            <div v-if="loading" class="text-center py-10">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Memuat riwayat absensi...
            </div>

            <div v-else-if="filteredRows.length === 0" class="text-center text-muted py-10">
                Belum ada data absensi di bulan ini.
            </div>

            <div v-else class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Foto Masuk</th>
                            <th>Jam Keluar</th>
                            <th>Foto Keluar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in filteredRows" :key="row.id">
                            <td>{{ formatDate(row.date) }}</td>
                            <td>
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span>{{ row.check_in_time ?? '-' }}</span>
                                    <span v-if="row.check_in_time && lateMinutes(row.check_in_time) > 0" class="badge badge-light-warning fs-8">
                                        Telat {{ formatDurationMinutes(lateMinutes(row.check_in_time)) }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <a v-if="photoUrl(row.check_in_photo)" :href="photoUrl(row.check_in_photo)!" target="_blank">
                                    <img :src="photoUrl(row.check_in_photo)!" class="rounded" style="width:40px;height:40px;object-fit:cover;" />
                                </a>
                                <span v-else class="text-muted fs-8">-</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span>{{ row.check_out_time ?? '-' }}</span>
                                    <span v-if="row.check_out_time && earlyLeaveMinutes(row.check_out_time) > 0" class="badge badge-light-warning fs-8">
                                        Pulang cepat {{ formatDurationMinutes(earlyLeaveMinutes(row.check_out_time)) }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <a v-if="photoUrl(row.check_out_photo)" :href="photoUrl(row.check_out_photo)!" target="_blank">
                                    <img :src="photoUrl(row.check_out_photo)!" class="rounded" style="width:40px;height:40px;object-fit:cover;" />
                                </a>
                                <span v-else class="text-muted fs-8">-</span>
                            </td>
                            <td>
                                <span class="badge" :class="statusBadge(row)">
                                    {{ statusLabel(row) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import axios from "@/libs/axios";

interface AttendanceRow {
    id: number;
    date: string;
    check_in_time: string | null;
    check_out_time: string | null;
    check_in_photo: string | null;
    check_out_photo: string | null;
    status: string | null;
}

function photoUrl(path: string | null) {
    return path ? `/storage/${path}` : null;
}

const rows = ref<AttendanceRow[]>([]);
const loading = ref(false);

function currentMonthValue() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
}

const selectedMonth = ref(currentMonthValue());

// Laravel ngirim kolom "date" (di-cast ke date) dalam format ISO lengkap
// (misal "2026-08-27T00:00:00.000000Z"), bukan cuma "2026-08-27" — jadi
// perbandingan/format-nya harus di-slice dulu, bukan string compare langsung.
function dateKey(dateStr: string) {
    return String(dateStr).slice(0, 10);
}

const filteredRows = computed(() =>
    rows.value
        .filter((r) => dateKey(r.date).startsWith(selectedMonth.value))
        .sort((a, b) => (dateKey(a.date) < dateKey(b.date) ? 1 : -1))
);

function formatDate(dateStr: string) {
    const d = new Date(dateKey(dateStr) + "T00:00:00");
    return d.toLocaleDateString("id-ID", {
        weekday: "short",
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

function normalizeTimeString(time: string) {
    if (!time) return "00:00:00";
    if (time.split(":").length === 2) return `${time}:00`;
    return time;
}

function formatDurationMinutes(totalMinutes: number) {
    const safeMinutes = Math.max(0, totalMinutes);
    const hours = Math.floor(safeMinutes / 60);
    const minutes = safeMinutes % 60;
    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
}

function calculateMinutesDifference(start: string, end: string) {
    const startDate = new Date(`2000-01-01T${normalizeTimeString(start)}`);
    const endDate = new Date(`2000-01-01T${normalizeTimeString(end)}`);
    return Math.round((endDate.getTime() - startDate.getTime()) / 60000);
}

function lateMinutes(checkInTime: string | null) {
    if (!checkInTime) return 0;
    const diff = calculateMinutesDifference("08:00", checkInTime);
    return diff > 0 ? diff : 0;
}

function earlyLeaveMinutes(checkOutTime: string | null) {
    if (!checkOutTime) return 0;
    const diff = calculateMinutesDifference(checkOutTime, "16:00");
    return diff > 0 ? diff : 0;
}

function statusLabel(row: AttendanceRow) {
    if (!row.check_in_time && !row.check_out_time) {
        if (row.status === "izin") return "Izin";
        if (row.status === "sakit") return "Sakit";
        return row.status ?? "Tidak Hadir";
    }

    if (row.check_in_time && row.check_out_time) {
        return row.status ?? "Hadir";
    }

    if (row.check_in_time && !row.check_out_time) return row.status ?? "Hadir";
    return row.status ?? "Tidak Hadir";
}

function statusBadge(row: AttendanceRow) {
    if (!row.check_in_time && !row.check_out_time) {
        if (row.status === "izin" || row.status === "sakit") return "badge-light-info";
        return "badge-light-danger";
    }

    if (row.check_in_time && row.check_out_time) {
        return "badge-light-success";
    }

    if (row.check_in_time && !row.check_out_time) return "badge-light-success";
    return "badge-light-danger";
}

async function fetchHistory() {
    loading.value = true;
    try {
        const res = await axios.get("/attendances");
        rows.value = res.data?.data ?? [];
    } catch (e) {
        console.error("Gagal memuat riwayat absensi:", e);
    } finally {
        loading.value = false;
    }
}

onMounted(fetchHistory);
</script> 