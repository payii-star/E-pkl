<template>
  <div class="row g-5">
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fs-2 fw-bold">{{ summary.total_interns ?? "—" }}</div>
          <div class="text-muted">Total Peserta Magang</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fs-2 fw-bold text-warning">{{ summary.pending_journals ?? "—" }}</div>
          <div class="text-muted">Jurnal Menunggu Approval</div>
          <router-link to="/journal/approval" class="fs-7">Lihat detail &rarr;</router-link>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="fs-2 fw-bold">{{ summary.total_tasks_given ?? "—" }}</div>
          <div class="text-muted">Tugas yang Diberikan</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import ApiService from "@/core/services/ApiService";

const summary = ref<Record<string, number>>({});

const fetchSummary = async () => {
  try {
    const { data } = await ApiService.get("/admin/summary");
    summary.value = data;
  } catch (error) {
    console.error(error);
  }
};

onMounted(fetchSummary);
</script>