<template>
  <!-- ============ CARD: ESTIMASI LAMA MAGANG ============ -->
  <div class="row g-5 g-xl-8">
    <div class="col-12">
      <div class="card card-xl-stretch mb-5 mb-xl-8">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1">Estimasi Lama Magang</span>
            <span class="text-muted fw-semibold fs-7">
              {{ monthRangeLabel }}
            </span>
          </h3>
        </div>
        <div class="card-body pt-0">
          <div v-if="estimation.status === 'incomplete'" class="text-center text-muted py-10">
            <KTIcon icon-name="information" icon-class="fs-3x mb-3 text-warning" />
            <div>Periode magang kamu belum diatur oleh admin.</div>
          </div>

          <template v-else>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <span class="fw-semibold fs-6 text-gray-600">
                Hari berjalan: <b>{{ estimation.days_passed }}</b> / {{ estimation.total_days }} hari
              </span>
              <span class="fw-bold fs-4 text-primary">{{ estimation.percentage }}%</span>
            </div>

            <div class="progress h-10px w-100 mb-3">
              <div
                class="progress-bar bg-primary"
                role="progressbar"
                :style="{ width: estimation.percentage + '%' }"
              ></div>
            </div>

            <div class="d-flex align-items-center">
              <KTIcon icon-name="time" icon-class="fs-3 text-muted me-2" />
              <span class="text-muted fw-semibold fs-7">
                <template v-if="estimation.status === 'not_started'">
                  Magang belum dimulai — <b>{{ estimation.days_remaining }} hari lagi</b>
                </template>
                <template v-else-if="estimation.status === 'completed'">
                  Magang sudah <b>selesai</b>
                </template>
                <template v-else>
                  Sisa waktu magang: <b>{{ estimation.days_remaining }} hari</b>
                </template>
              </span>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ CARD: TUGAS DARI ADMIN ============ -->
  <div class="row g-5 g-xl-8">
    <div class="col-12">
      <div class="card card-xl-stretch mb-5 mb-xl-8">
        <div class="card-header border-0 pt-5">
          <h3 class="card-title align-items-start flex-column">
            <span class="card-label fw-bold fs-3 mb-1">Tugas dari Admin</span>
            <span class="text-muted fw-semibold fs-7">Daftar tugas yang perlu dikerjakan</span>
          </h3>
        </div>
        <div class="card-body pt-0">
          <div v-if="tasks.length === 0" class="text-center text-muted py-10">
            Belum ada tugas dari admin.
          </div>

          <div
            v-for="task in tasks"
            :key="task.id"
            class="d-flex align-items-center mb-6"
          >
            <div class="symbol symbol-45px me-5">
              <span class="symbol-label" :class="statusBg(task.status)">
                <KTIcon :icon-name="statusIcon(task.status)" icon-class="fs-2 text-white" />
              </span>
            </div>

            <div class="d-flex flex-column flex-grow-1">
              <a href="#" class="text-dark text-hover-primary fw-bold fs-6">{{ task.title }}</a>
              <span class="text-muted fw-semibold fs-7">{{ task.description }}</span>
            </div>

            <div class="d-flex flex-column align-items-end ms-3">
              <span class="badge fs-8" :class="statusBadge(task.status)">
                {{ statusLabel(task.status) }}
              </span>
              <span class="text-muted fs-8 mt-1">Deadline: {{ formatDate(task.due_date) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, onMounted, onUnmounted } from "vue";
import ApiService from "@/core/services/ApiService";

// --- Tipe Data ---
interface EstimationData {
  start_date: string;
  end_date: string;
  total_days: number;
  days_passed: number;
  days_remaining: number;
  percentage: number;
  status: "incomplete" | "invalid" | "not_started" | "active" | "completed" | "error";
}

type TaskStatus = "belum" | "sedang" | "selesai";

interface TaskItem {
  id: number;
  title: string;
  description: string;
  status: TaskStatus;
  due_date: string;
}

// --- State ---
const estimation = reactive<EstimationData>({
  start_date: "",
  end_date: "",
  total_days: 0,
  days_passed: 0,
  days_remaining: 0,
  percentage: 0,
  status: "incomplete",
});

const tasks = reactive<TaskItem[]>([]);

// --- Label "Bulan X — Bulan Y" ---
const monthRangeLabel = computed(() => {
  if (estimation.status === "incomplete" || !estimation.start_date || !estimation.end_date) {
    return "Periode belum diatur";
  }
  const start = new Date(estimation.start_date);
  const end = new Date(estimation.end_date);
  const fmt = (d: Date) => d.toLocaleDateString("id-ID", { month: "long", year: "numeric" });
  const startLabel = fmt(start);
  const endLabel = fmt(end);
  return startLabel === endLabel ? startLabel : `${startLabel} — ${endLabel}`;
});

// --- Helper tampilan status ---
const statusLabel = (status: TaskStatus) => {
  const map: Record<TaskStatus, string> = {
    belum: "Belum Dikerjakan",
    sedang: "Sedang Dikerjakan",
    selesai: "Selesai",
  };
  return map[status] ?? status;
};

const statusBadge = (status: TaskStatus) => {
  const map: Record<TaskStatus, string> = {
    belum: "badge-light-secondary",
    sedang: "badge-light-warning",
    selesai: "badge-light-success",
  };
  return map[status] ?? "badge-light-secondary";
};

const statusBg = (status: TaskStatus) => {
  const map: Record<TaskStatus, string> = {
    belum: "bg-secondary",
    sedang: "bg-warning",
    selesai: "bg-success",
  };
  return map[status] ?? "bg-secondary";
};

const statusIcon = (status: TaskStatus) => {
  const map: Record<TaskStatus, string> = {
    belum: "time",
    sedang: "loading",
    selesai: "check-circle",
  };
  return map[status] ?? "time";
};

const formatDate = (dateStr: string) => {
  if (!dateStr) return "-";
  return new Date(dateStr).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
};

// --- API Fetch ---
const fetchEstimation = () => {
  ApiService.get("/intern/estimation")
    .then(({ data }) => {
      const d = data.data ?? {};
      estimation.start_date = d.tanggal_mulai ?? "";
      estimation.end_date = d.tanggal_selesai ?? "";
      estimation.total_days = d.total_hari ?? 0;
      estimation.days_passed = d.hari_berjalan ?? 0;
      estimation.days_remaining = d.hari_tersisa ?? 0;
      estimation.percentage = d.progress ?? 0;
      estimation.status = d.status ?? "incomplete";
    })
    .catch(({ response }) => {
      console.error("Error fetching estimation:", response);
    });
};

const fetchTasks = () => {
  ApiService.get("/intern/tasks")
    .then(({ data }) => {
      tasks.splice(0, tasks.length, ...data.data);
    })
    .catch(({ response }) => {
      console.error("Error fetching tasks:", response);
    });
};

// --- Refresh berkala biar "real-time" (tiap 5 menit cukup, hari cuma ganti 1x/hari) ---
let refreshInterval: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
  fetchEstimation();
  fetchTasks();
  refreshInterval = setInterval(fetchEstimation, 5 * 60 * 1000);
});

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval);
});
</script>