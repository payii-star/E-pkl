<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";

interface Attachment {
    id: number;
    path: string;
    original_name: string;
    mime_type: string;
    url: string;
    is_image: boolean;
}

interface Task {
    id: number;
    title: string;
    description: string | null;
    status: "belum" | "sedang" | "submitted" | "revisi" | "selesai" | "ditolak";
    due_date: string | null;
    submission_note: string | null;
    admin_note: string | null;
    submitted_at: string | null;
    attachments: Attachment[];
}

const tasks = ref<Task[]>([]);
const loading = ref(false);
const updatingId = ref<number | null>(null);

// Detail "card" (modal) yang lagi dibuka
const selectedTask = ref<Task | null>(null);

const submitFiles = ref<File[]>([]);
const submitNote = ref("");
const submitting = ref(false);
const submitMsg = ref("");
const downloadingZip = ref(false);
const previewImageUrl = ref<string | null>(null);

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

const imageAttachments = computed(() =>
    selectedTask.value?.attachments.filter((a) => a.is_image) ?? []
);
const fileAttachments = computed(() =>
    selectedTask.value?.attachments.filter((a) => !a.is_image) ?? []
);

function canSubmit(task: Task) {
    return ["belum", "sedang", "revisi", "ditolak"].includes(task.status);
}

function formatDate(dateStr: string | null) {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

function formatDateTime(dateStr: string | null) {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleString("id-ID", {
        day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit",
    });
}

function fetchTasks() {
    loading.value = true;
    axios
        .get("/intern/tasks")
        .then(({ data }) => {
            tasks.value = data.data ?? [];
            // Kalau lagi buka detail salah satu tugas, refresh datanya juga
            if (selectedTask.value) {
                const fresh = tasks.value.find((t) => t.id === selectedTask.value!.id);
                if (fresh) selectedTask.value = fresh;
            }
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? "Gagal memuat daftar tugas");
        })
        .finally(() => {
            loading.value = false;
        });
}

function openDetail(task: Task) {
    selectedTask.value = task;
    submitFiles.value = [];
    submitNote.value = "";
    submitMsg.value = "";
}

function closeDetail() {
    selectedTask.value = null;
}

function openImagePreview(url: string) {
    previewImageUrl.value = url;
}

function closeImagePreview() {
    previewImageUrl.value = null;
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
            toast.error(err.response?.data?.message ?? "Gagal memperbarui status");
        })
        .finally(() => {
            updatingId.value = null;
        });
}

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const newFiles = input.files ? Array.from(input.files) : [];
    // Ditambahin ke daftar yang udah ada, bukan ngeganti — biar bisa
    // buka dialog "Choose Files" berkali-kali buat nambah file satu-satu.
    submitFiles.value = [...submitFiles.value, ...newFiles];
    // Reset input biar bisa milih file yang sama lagi kalau perlu
    input.value = "";
}

function removeSelectedFile(index: number) {
    submitFiles.value.splice(index, 1);
}

function submitTask() {
    if (!selectedTask.value) return;
    if (submitFiles.value.length === 0) {
        submitMsg.value = "Pilih minimal 1 file/gambar tugas dulu";
        return;
    }

    const formData = new FormData();
    submitFiles.value.forEach((file) => formData.append("attachments[]", file));
    if (submitNote.value) formData.append("submission_note", submitNote.value);

    submitting.value = true;
    submitMsg.value = "";

    axios
        .post(`/tasks/${selectedTask.value.id}/submit`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        })
        .then(() => {
            toast.success("Tugas berhasil dikumpulkan");
            submitFiles.value = [];
            submitNote.value = "";
            fetchTasks();
        })
        .catch((err: any) => {
            submitMsg.value = err.response?.data?.message ?? "Gagal mengumpulkan tugas";
        })
        .finally(() => {
            submitting.value = false;
        });
}

function downloadZip() {
    if (!selectedTask.value) return;
    downloadingZip.value = true;

    axios
        .get(`/tasks/${selectedTask.value.id}/attachments/zip`, { responseType: "blob" })
        .then((res) => {
            const url = window.URL.createObjectURL(new Blob([res.data]));
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", `tugas-${selectedTask.value!.id}-lampiran.zip`);
            document.body.appendChild(link);
            link.click();
            link.remove();
        })
        .catch(() => {
            toast.error("Gagal mengunduh lampiran");
        })
        .finally(() => {
            downloadingZip.value = false;
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

            <!-- List cuma nampilin judul + status, diklik buka detail -->
            <div v-else class="d-flex flex-column gap-2">
                <button
                    v-for="task in tasks"
                    :key="task.id"
                    type="button"
                    class="btn btn-flex btn-light text-start justify-content-between align-items-center py-4"
                    @click="openDetail(task)"
                >
                    <div>
                        <div class="fw-bold text-gray-800">{{ task.title }}</div>
                        <div v-if="task.due_date" class="text-muted fs-8">Deadline: {{ formatDate(task.due_date) }}</div>
                    </div>
                    <span class="badge" :class="statusBadge[task.status]">
                        {{ statusLabel[task.status] }}
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ══ CARD DETAIL (modal) ══ -->
    <div v-if="selectedTask" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.6)">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h4 class="modal-title fw-bold mb-1">{{ selectedTask.title }}</h4>
                        <span class="badge" :class="statusBadge[selectedTask.status]">
                            {{ statusLabel[selectedTask.status] }}
                        </span>
                    </div>
                    <button class="btn btn-sm btn-icon btn-light" @click="closeDetail">
                        <KTIcon icon-name="cross" icon-class="fs-4" />
                    </button>
                </div>

                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Info dasar -->
                    <div v-if="selectedTask.due_date" class="text-muted fs-7 mb-2">
                        <b>Deadline:</b> {{ formatDate(selectedTask.due_date) }}
                    </div>
                    <p v-if="selectedTask.description" class="text-gray-700 mb-4">
                        {{ selectedTask.description }}
                    </p>

                    <!-- Catatan revisi/penolakan admin -->
                    <div v-if="selectedTask.admin_note && (selectedTask.status === 'revisi' || selectedTask.status === 'ditolak')"
                         class="alert alert-danger py-3 fs-7 mb-4">
                        <b>Catatan dari admin:</b> {{ selectedTask.admin_note }}
                    </div>
                    <div v-else-if="selectedTask.admin_note && selectedTask.status === 'selesai'"
                         class="alert alert-success py-3 fs-7 mb-4">
                        <b>Catatan admin:</b> {{ selectedTask.admin_note }}
                    </div>

                    <!-- Lampiran yang sudah dikumpulkan -->
                    <template v-if="selectedTask.attachments.length">
                        <div class="separator my-4"></div>
                        <div class="fw-bold mb-3">Lampiran Terkumpul</div>

                        <!-- Galeri gambar: grid, otomatis menyesuaikan kalau banyak -->
                        <div v-if="imageAttachments.length" class="mb-4">
                            <div class="row g-2">
                                <div
                                    v-for="img in imageAttachments"
                                    :key="img.id"
                                    :class="imageAttachments.length > 5 ? 'col-3 col-md-2' : 'col-4 col-md-3'"
                                >
                                    <div
                                        role="button"
                                        tabindex="0"
                                        class="card border-0 overflow-hidden shadow-sm h-100"
                                        style="cursor:pointer; user-select:none;"
                                        @click="openImagePreview(img.url)"
                                        @keydown.enter.prevent="openImagePreview(img.url)"
                                        @keydown.space.prevent="openImagePreview(img.url)"
                                    >
                                        <img :src="img.url" class="w-100"
                                             style="aspect-ratio:1/1; object-fit:cover; display:block;" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- File non-gambar: cukup daftar nama, didownload sekaligus lewat ZIP -->
                        <div v-if="fileAttachments.length" class="mb-3">
                            <div v-for="f in fileAttachments" :key="f.id" class="d-flex align-items-center gap-2 fs-7 text-muted mb-1">
                                <KTIcon icon-name="file" icon-class="fs-6" />
                                {{ f.original_name }}
                            </div>
                        </div>

                        <button class="btn btn-sm btn-light-primary mb-2" :disabled="downloadingZip" @click="downloadZip">
                            <span v-if="downloadingZip" class="spinner-border spinner-border-sm me-2"></span>
                            <KTIcon v-else icon-name="folder-down" icon-class="fs-6 me-1" />
                            Download Semua Lampiran (ZIP)
                        </button>

                        <div v-if="selectedTask.submission_note" class="text-muted fs-8 mt-2">
                            Catatan kamu: {{ selectedTask.submission_note }}
                        </div>
                        <div v-if="selectedTask.submitted_at" class="text-muted fs-8">
                            Dikumpulkan: {{ formatDateTime(selectedTask.submitted_at) }}
                        </div>
                    </template>

                    <!-- Progress belum/sedang -->
                    <template v-if="selectedTask.status === 'belum' || selectedTask.status === 'sedang'">
                        <div class="separator my-4"></div>
                        <div class="fw-semibold fs-7 mb-2">Progress</div>
                        <div class="d-flex gap-2 mb-2">
                            <button
                                v-for="s in ['belum', 'sedang']"
                                :key="s"
                                type="button"
                                class="btn btn-sm"
                                :class="selectedTask.status === s ? statusBadge[s] : 'btn-light'"
                                :disabled="updatingId === selectedTask.id"
                                @click="updateStatus(selectedTask, s as 'belum' | 'sedang')"
                            >
                                {{ statusLabel[s] }}
                            </button>
                        </div>
                    </template>

                    <!-- Form kumpulkan tugas -->
                    <template v-if="canSubmit(selectedTask)">
                        <div class="separator my-4"></div>
                        <div class="fw-bold mb-3">
                            {{ selectedTask.status === 'revisi' || selectedTask.status === 'ditolak' ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas' }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-7 fw-semibold">File / Gambar Tugas (bisa pilih lebih dari satu)</label>
                            <input type="file" class="form-control form-control-sm" multiple
                                   accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.zip"
                                   @change="onFileChange" />
                        </div>

                        <div v-if="submitFiles.length" class="mb-3">
                            <div v-for="(f, i) in submitFiles" :key="i" class="d-flex align-items-center justify-content-between fs-8 text-muted mb-1">
                                <span><KTIcon icon-name="file" icon-class="fs-7 me-1" />{{ f.name }}</span>
                                <button class="btn btn-icon btn-sm btn-light-danger" style="width:20px;height:20px;" @click="removeSelectedFile(i)">
                                    <KTIcon icon-name="cross" icon-class="fs-8" />
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fs-7 fw-semibold">Catatan (opsional)</label>
                            <textarea v-model="submitNote" class="form-control form-control-sm" rows="2"
                                      placeholder="Ada yang mau disampaikan ke admin?"></textarea>
                        </div>

                        <div v-if="submitMsg" class="alert alert-danger py-2 fs-7 mb-3">{{ submitMsg }}</div>

                        <button class="btn btn-primary" :disabled="submitting" @click="submitTask">
                            <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                            Kirim Tugas
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <div v-if="previewImageUrl" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.7)">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 bg-transparent shadow-none">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-icon btn-light" @click="closeImagePreview">
                        <KTIcon icon-name="cross" icon-class="fs-4" />
                    </button>
                </div>
                <img :src="previewImageUrl" class="rounded shadow" style="max-height:80vh; width:100%; object-fit:contain; background:#000;" />
            </div>
        </div>
    </div>
</template>