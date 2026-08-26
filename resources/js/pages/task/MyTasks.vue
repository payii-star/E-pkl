<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: "belum" | "sedang" | "selesai";
    due_date: string | null;
}

const tasks = ref<Task[]>([]);
const loading = ref(false);
const updatingId = ref<number | null>(null);

const statusLabel: Record<string, string> = {
    belum: "Belum Dikerjakan",
    sedang: "Sedang Dikerjakan",
    selesai: "Selesai",
};

const statusBadge: Record<string, string> = {
    belum: "badge-light-warning",
    sedang: "badge-light-primary",
    selesai: "badge-light-success",
};

const belumCount = computed(
    () => tasks.value.filter((t) => t.status !== "selesai").length
);

function fetchTasks() {
    loading.value = true;
    axios
        .get("/intern/tasks")
        .then(({ data }) => {
            tasks.value = data.data ?? [];
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ?? "Gagal memuat daftar tugas"
            );
        })
        .finally(() => {
            loading.value = false;
        });
}

function updateStatus(task: Task, status: Task["status"]) {
    if (task.status === status) return;
    updatingId.value = task.id;

    axios
        .patch(`/tasks/${task.id}/status`, { status })
        .then(({ data }) => {
            task.status = data.data.status;
            toast.success("Status tugas diperbarui");
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ?? "Gagal memperbarui status"
            );
        })
        .finally(() => {
            updatingId.value = null;
        });
}

onMounted(fetchTasks);
</script>

<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title d-flex flex-column">
                <h2 class="mb-1">Tugas Saya</h2>
                <div class="text-gray-500 fs-7">
                    {{ belumCount }} tugas belum selesai
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <div v-if="loading" class="text-center py-10">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Memuat tugas...
            </div>

            <div v-else-if="!tasks.length" class="text-center text-muted py-10">
                Belum ada tugas yang diberikan untukmu.
            </div>

            <div v-else class="d-flex flex-column gap-4">
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    class="border rounded p-5"
                >
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <div>
                            <div class="fw-bold fs-5 text-gray-800">{{ task.title }}</div>
                            <div v-if="task.due_date" class="text-muted fs-7 mt-1">
                                Deadline: {{ task.due_date }}
                            </div>
                        </div>
                        <span class="badge" :class="statusBadge[task.status]">
                            {{ statusLabel[task.status] }}
                        </span>
                    </div>

                    <p v-if="task.description" class="text-gray-600 fs-7 mb-4">
                        {{ task.description }}
                    </p>

                    <div class="d-flex gap-2">
                        <button
                            v-for="s in ['belum', 'sedang', 'selesai']"
                            :key="s"
                            type="button"
                            class="btn btn-sm"
                            :class="task.status === s ? statusBadge[s] : 'btn-light'"
                            :disabled="updatingId === task.id"
                            @click="updateStatus(task, s as Task['status'])"
                        >
                            {{ statusLabel[s] }}
                        </button>
                    </div>         
                </div>
            </div>
        </div>
    </div>
</template>