<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: "belum" | "sedang" | "submitted" | "revisi" | "selesai" | "ditolak";
    due_date: string | null;
    attachment: string | null;
    attachment_url: string | null;
    submission_note: string | null;
    admin_note: string | null;
    submitted_at: string | null;
    reviewed_at: string | null;
}

const tasks = ref<Task[]>([]);
const loading = ref(false);
const updatingId = ref<number | null>(null);

const showSubmitForm = ref<number | null>(null);
const submitNote = ref("");
const submitFile = ref<File | null>(null);
const submitting = ref(false);
const submitMsg = ref("");

const statusLabel: Record<string, string> = {
    belum: "Belum Dikerjakan",
    sedang: "Sedang Dikerjakan",
    submitted: "Menunggu Review",
    revisi: "Perlu Revisi",
    selesai: "Selesai",
    ditolak: "Ditolak",
};

const statusBadge: Record<string, string> = {
    belum: "badge-light-warning",
    sedang: "badge-light-primary",
    submitted: "badge-light-info",
    revisi: "badge-light-danger",
    selesai: "badge-light-success",
    ditolak: "badge-light-danger",
};

const belumCount = computed(
    () => tasks.value.filter((t) => t.status !== "selesai").length
);

function canSubmit(task: Task) {
    return ["belum", "sedang", "revisi", "ditolak"].includes(task.status);
}

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

function updateStatus(task: Task, status: "belum" | "sedang") {
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

function openSubmitForm(task: Task) {
    showSubmitForm.value = task.id;
    submitNote.value = "";
    submitFile.value = null;
    submitMsg.value = "";
}

function closeSubmitForm() {
    showSubmitForm.value = null;
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    submitFile.value = file ?? null;
}

function submitTask(task: Task) {
    if (!submitFile.value) {
        submitMsg.value = "Pilih file/gambar tugas dulu";
        return;
    }

    const formData = new FormData();
    formData.append("attachment", submitFile.value);
    if (submitNote.value) formData.append("submission_note", submitNote.value);

    submitting.value = true;
    submitMsg.value = "";

    axios
        .post(`/tasks/${task.id}/submit`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        })
        .then(() => {
            toast.success("Tugas berhasil dikumpulkan");
            showSubmitForm.value = null;
            fetchTasks();
        })
        .catch((err: any) => {
            submitMsg.value =
                err.response?.data?.message ?? "Gagal mengumpulkan tugas";
        })
        .finally(() => {
            submitting.value = false;
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

                    <!-- Catatan revisi/penolakan dari admin -->
                    <div v-if="task.admin_note && (task.status === 'revisi' || task.status === 'ditolak')"
                         class="alert alert-danger py-3 fs-7 mb-4">
                        <b>Catatan dari admin:</b> {{ task.admin_note }}
                    </div>

                    <!-- File yang sudah dikumpulkan -->
                    <div v-if="task.attachment_url && task.status !== 'belum' && task.status !== 'sedang'" class="mb-4">
                        <a :href="task.attachment_url" target="_blank" class="btn btn-sm btn-light-primary">
                            <KTIcon icon-name="paper-clip" icon-class="fs-6 me-1" />
                            Lihat File Terkumpul
                        </a>
                        <div v-if="task.submission_note" class="text-muted fs-8 mt-2">
                            Catatan kamu: {{ task.submission_note }}
                        </div>
                    </div>

                    <!-- Progress belum/sedang (cuma kalau belum pernah submit) -->
                    <div v-if="task.status === 'belum' || task.status === 'sedang'" class="d-flex gap-2 mb-3">
                        <button
                            v-for="s in ['belum', 'sedang']"
                            :key="s"
                            type="button"
                            class="btn btn-sm"
                            :class="task.status === s ? statusBadge[s] : 'btn-light'"
                            :disabled="updatingId === task.id"
                            @click="updateStatus(task, s as 'belum' | 'sedang')"
                        >
                            {{ statusLabel[s] }}
                        </button>
                    </div>

                    <!-- Tombol kumpulkan tugas -->
                    <div v-if="canSubmit(task)">
                        <button
                            v-if="showSubmitForm !== task.id"
                            class="btn btn-sm btn-primary"
                            @click="openSubmitForm(task)"
                        >
                            <KTIcon icon-name="cloud-add" icon-class="fs-6 me-1" />
                            {{ task.status === 'revisi' || task.status === 'ditolak' ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas' }}
                        </button>

                        <div v-else class="border rounded p-4 bg-light-secondary bg-opacity-25">
                            <div class="mb-3">
                                <label class="form-label fs-7 fw-semibold">File / Gambar Tugas</label>
                                <input type="file" class="form-control form-control-sm"
                                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                                       @change="onFileChange" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label fs-7 fw-semibold">Catatan (opsional)</label>
                                <textarea v-model="submitNote" class="form-control form-control-sm" rows="2"
                                          placeholder="Ada yang mau disampaikan ke admin?"></textarea>
                            </div>
                            <div v-if="submitMsg" class="alert alert-danger py-2 fs-7 mb-3">{{ submitMsg }}</div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-light" @click="closeSubmitForm">Batal</button>
                                <button class="btn btn-sm btn-primary" :disabled="submitting" @click="submitTask(task)">
                                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                    Kirim
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>