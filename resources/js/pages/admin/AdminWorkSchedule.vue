<script setup lang="ts">
import { ref } from "vue";

interface DaySchedule {
  day: string;
  label: string;
  active: boolean;
  startTime: string;
  endTime: string;
}

const schedules = ref<DaySchedule[]>([
  { day: "monday", label: "Senin", active: true, startTime: "08:00", endTime: "17:00" },
  { day: "tuesday", label: "Selasa", active: true, startTime: "08:00", endTime: "17:00" },
  { day: "wednesday", label: "Rabu", active: true, startTime: "08:00", endTime: "17:00" },
  { day: "thursday", label: "Kamis", active: true, startTime: "08:00", endTime: "17:00" },
  { day: "friday", label: "Jumat", active: true, startTime: "08:00", endTime: "17:00" },
  { day: "saturday", label: "Sabtu", active: false, startTime: "08:00", endTime: "13:00" },
  { day: "sunday", label: "Minggu", active: false, startTime: "08:00", endTime: "13:00" },
]);

const loading = ref(false);
const saving = ref(false);
const errorMsg = ref("");
const successMsg = ref("");

async function loadSchedule() {
  loading.value = true;
  errorMsg.value = "";
  try {
    // TODO: sambungin ke API backend, contoh:
    // const res = await ApiService.get("/admin/work-schedule");
    // schedules.value = res.data.data;
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || "Gagal memuat jadwal kerja.";
  } finally {
    loading.value = false;
  }
}

async function saveSchedule() {
  saving.value = true;
  errorMsg.value = "";
  successMsg.value = "";
  try {
    // TODO: sambungin ke API backend, contoh:
    // await ApiService.post("/admin/work-schedule", { schedules: schedules.value });
    successMsg.value = "Jadwal kerja berhasil disimpan.";
  } catch (e: any) {
    errorMsg.value = e?.response?.data?.message || "Gagal menyimpan jadwal kerja.";
  } finally {
    saving.value = false;
  }
}

loadSchedule();
</script>

<template>
  <div class="card">
    <div class="card-header border-0 pt-6">
      <div class="card-title">
        <h2>Pengaturan Hari & Jam Kerja</h2>
      </div>
    </div>

    <div class="card-body pt-0">
      <div v-if="errorMsg" class="alert alert-danger d-flex align-items-center p-4 mb-6">
        <span>{{ errorMsg }}</span>
      </div>
      <div v-if="successMsg" class="alert alert-success d-flex align-items-center p-4 mb-6">
        <span>{{ successMsg }}</span>
      </div>

      <div v-if="loading" class="d-flex justify-content-center py-10">
        <span class="spinner-border text-primary"></span>
      </div>

      <div v-else class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-4">
          <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
              <th class="min-w-100px">Hari</th>
              <th class="min-w-100px">Aktif</th>
              <th class="min-w-125px">Jam Masuk</th>
              <th class="min-w-125px">Jam Pulang</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="schedule in schedules" :key="schedule.day">
              <td class="fw-semibold">{{ schedule.label }}</td>
              <td>
                <div class="form-check form-switch form-check-custom form-check-solid">
                  <input
                    v-model="schedule.active"
                    class="form-check-input"
                    type="checkbox"
                  />
                </div>
              </td>
              <td>
                <input
                  v-model="schedule.startTime"
                  type="time"
                  class="form-control form-control-solid"
                  :disabled="!schedule.active"
                />
              </td>
              <td>
                <input
                  v-model="schedule.endTime"
                  type="time"
                  class="form-control form-control-solid"
                  :disabled="!schedule.active"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end mt-6">
        <button
          type="button"
          class="btn btn-primary"
          :disabled="saving || loading"
          @click="saveSchedule"
        >
          {{ saving ? "Menyimpan..." : "Simpan Perubahan" }}
        </button>
      </div>
    </div>
  </div>
</template>