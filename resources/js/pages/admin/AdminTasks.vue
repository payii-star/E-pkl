<template>
    <div class="row g-5">
        <!-- ══ LIST TASK (kiri, lebar) ══ -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">
                            {{ selectedUser ? `List Task — ${selectedUser.name}` : 'List Task' }}
                        </h2>
                    </div>
                    <div v-if="selectedUser" class="card-toolbar">
                        <button class="btn btn-icon btn-sm btn-primary rounded-circle" @click="openCreate" title="Beri Tugas">
                            <KTIcon icon-name="plus" icon-class="fs-4" />
                        </button>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <!-- Belum pilih user -->
                    <div v-if="!selectedUserId" class="text-center py-20 text-muted">
                        <KTIcon icon-name="user" icon-class="fs-3x mb-3 d-block" />
                        <div class="fs-5 fw-semibold mb-1">Pilih user</div>
                        <div class="fs-7">Pilih salah satu nama di panel kanan untuk melihat & kelola tugasnya</div>
                    </div>

                    <template v-else>
                        <div class="mb-3">
                            <select v-model="sortBy" class="form-select form-select-sm" style="max-width: 220px;">
                                <option value="newest">Terbaru</option>
                                <option value="oldest">Terlama</option>
                                <option value="deadline_asc">Deadline terdekat</option>
                                <option value="deadline_desc">Deadline terjauh</option>
                            </select>
                        </div>

                        <div v-if="userTasks.length === 0" class="text-center text-muted py-10 fs-7">
                            Belum ada tugas untuk user ini.
                        </div>

                        <div v-else class="d-flex flex-column gap-2">
                            <div
                                v-for="task in userTasks"
                                :key="task.id"
                                role="button"
                                tabindex="0"
                                class="border rounded p-3 d-flex align-items-start gap-3"
                                :class="[
                                    selectedTaskId === task.id ? 'bg-light-primary' : '',
                                    'border-start border-4',
                                    taskBorderClass[task.status],
                                ]"
                                style="cursor: pointer;"
                                @click="selectedTaskId = task.id"
                            >
                                <KTIcon
                                    :icon-name="task.status === 'selesai' ? 'check-square' : 'square'"
                                    :icon-class="task.status === 'selesai' ? 'fs-2 text-success' : 'fs-2 text-muted'"
                                />
                                <div class="flex-grow-1">
                                    <div class="fw-semibold fs-7" :class="task.status === 'selesai' ? 'text-muted text-decoration-line-through' : 'text-gray-800'">
                                        {{ task.title }}
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span v-if="task.due_date" class="text-muted fs-9">
                                            <KTIcon icon-name="calendar" icon-class="fs-8 me-1" />{{ formatDate(task.due_date) }}
                                        </span>
                                        <span class="badge fs-9" :class="statusBadge[task.status]">{{ statusLabel[task.status] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ══ KANAN: DAFTAR USER + FILES + HISTORY ══ -->
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-5">
                <!-- Daftar User (kompak) -->
                <div class="card">
                    <div class="card-header border-0 pt-6 min-h-auto">
                        <div class="card-title">
                            <h3 class="fs-6 fw-bold">Daftar User</h3>
                        </div>
                        <div class="card-toolbar">
                            <button class="btn btn-icon btn-sm btn-light" @click="showSearch = !showSearch" title="Cari user">
                                <KTIcon icon-name="magnifier" icon-class="fs-5" />
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <input
                            v-if="showSearch"
                            v-model="searchQuery"
                            type="text"
                            class="form-control form-control-sm mb-3"
                            placeholder="Cari nama / email..."
                        />

                        <div v-if="loading" class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </div>
                        <div v-else-if="!filteredInterns.length" class="text-center text-muted fs-8 py-3">
                            Tidak ada user yang cocok.
                        </div>
                        <div v-else class="d-flex flex-wrap gap-2">
                            <div
                                v-for="u in filteredInterns"
                                :key="u.id"
                                role="button"
                                tabindex="0"
                                class="text-center"
                                style="width: 60px; cursor: pointer;"
                                :title="u.name"
                                @click="selectUser(u.id)"
                            >
                                <div
                                    class="symbol symbol-40px mx-auto mb-1"
                                    :class="selectedUserId === u.id ? 'border border-3 border-primary rounded-circle' : ''"
                                >
                                    <img v-if="u.photo" :src="resolvePhotoUrl(u.photo)" class="rounded-circle" />
                                    <span v-else class="symbol-label bg-light-primary text-primary fw-bold rounded-circle">
                                        {{ u.name?.charAt(0)?.toUpperCase() }}
                                    </span>
                                </div>
                                <div class="fs-9 text-truncate">{{ u.name }}</div>
                                <span class="badge badge-light-success fs-9">{{ taskCountFor(u.id) }}x</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Files & History, muncul kalau ada tugas terpilih -->
                <template v-if="selectedTask">
                    <!-- Header tugas terpilih -->
                    <div class="card">
                        <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <div class="fw-bold fs-6">{{ selectedTask.title }}</div>
                                <p v-if="selectedTask.description" class="text-muted fs-8 mb-1">{{ selectedTask.description }}</p>
                                <span v-if="selectedTask.due_date" class="text-muted fs-9">Deadline: {{ formatDate(selectedTask.due_date) }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge fs-9" :class="statusBadge[selectedTask.status]">{{ statusLabel[selectedTask.status] }}</span>
                                <button
                                    class="btn btn-icon btn-sm btn-light-danger"
                                    :disabled="deletingId === selectedTask.id"
                                    @click="removeTask(selectedTask)"
                                >
                                    <span v-if="deletingId === selectedTask.id" class="spinner-border spinner-border-sm"></span>
                                    <KTIcon v-else icon-name="trash" icon-class="fs-6" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Files -->
                    <div class="card">
                        <div class="card-header border-0 pt-6 min-h-auto">
                            <div class="card-title">
                                <h3 class="fs-6 fw-bold">Files</h3>
                            </div>
                            <div v-if="selectedTask.attachments?.length" class="card-toolbar">
                                <button class="btn btn-sm btn-light-primary" :disabled="downloadingZipId === selectedTask.id" @click="downloadZip(selectedTask)">
                                    <span v-if="downloadingZipId === selectedTask.id" class="spinner-border spinner-border-sm me-2"></span>
                                    <KTIcon v-else icon-name="folder-down" icon-class="fs-6 me-1" />
                                    ZIP
                                </button>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div v-if="!selectedTask.attachments?.length" class="text-muted fs-8">Belum ada lampiran.</div>
                            <div v-else>
                                <div
                                    v-if="imageAttachments(selectedTask).length"
                                    class="mb-3"
                                    style="display:grid; grid-template-columns: repeat(auto-fill, minmax(80px, 100px)); gap:0.5rem;"
                                >
                                    <div v-for="img in imageAttachments(selectedTask)" :key="img.id">
                                        <div
                                            role="button"
                                            tabindex="0"
                                            class="card border-0 overflow-hidden shadow-sm h-100"
                                            style="cursor:pointer; user-select:none;"
                                            @click="openImagePreview(img.url)"
                                        >
                                            <img :src="img.url" class="w-100" style="aspect-ratio:1/1; object-fit:cover; display:block;" />
                                        </div>
                                    </div>
                                </div>
                                <div v-if="fileAttachments(selectedTask).length" class="d-flex flex-column gap-1">
                                    <a
                                        v-for="f in fileAttachments(selectedTask)"
                                        :key="f.id"
                                        :href="f.url"
                                        target="_blank"
                                        class="d-flex align-items-center gap-2 fs-8 text-gray-700 text-hover-primary"
                                    >
                                        <KTIcon icon-name="file" icon-class="fs-7" />
                                        {{ f.original_name }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History -->
                    <div class="card">
                        <div class="card-header border-0 pt-6 min-h-auto">
                            <div class="card-title">
                                <h3 class="fs-6 fw-bold">History</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="d-flex flex-column gap-4">
                                <!-- Diberikan -->
                                <div class="d-flex gap-3">
                                    <div class="symbol symbol-30px">
                                        <span class="symbol-label bg-light-primary text-primary">
                                            <KTIcon icon-name="send" icon-class="fs-7" />
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold fs-8">
                                            Tugas diberikan{{ selectedTask.creator?.name ? ` oleh ${selectedTask.creator.name}` : '' }}
                                        </div>
                                        <div class="text-muted fs-9">{{ formatDateTime(selectedTask.created_at) }}</div>
                                    </div>
                                </div>

                                <!-- Dikumpulkan -->
                                <div v-if="selectedTask.submitted_at" class="d-flex gap-3">
                                    <div class="symbol symbol-30px">
                                        <span class="symbol-label bg-light-info text-info">
                                            <KTIcon icon-name="folder-up" icon-class="fs-7" />
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold fs-8">Dikumpulkan oleh {{ selectedUser?.name }}</div>
                                        <div class="text-muted fs-9">{{ formatDateTime(selectedTask.submitted_at) }}</div>
                                        <div v-if="selectedTask.submission_note" class="fs-8 text-gray-700 mt-1">
                                            "{{ selectedTask.submission_note }}"
                                        </div>
                                    </div>
                                </div>

                                <!-- Direview -->
                                <div v-if="selectedTask.reviewed_at" class="d-flex gap-3">
                                    <div class="symbol symbol-30px">
                                        <span class="symbol-label" :class="reviewIconBg[selectedTask.status] ?? 'bg-light-secondary text-secondary'">
                                            <KTIcon icon-name="check" icon-class="fs-7" />
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold fs-8">
                                            Direview — <span :class="'text-' + (statusColor[selectedTask.status] ?? 'secondary')">{{ statusLabel[selectedTask.status] }}</span>
                                        </div>
                                        <div class="text-muted fs-9">{{ formatDateTime(selectedTask.reviewed_at) }}</div>
                                        <div v-if="selectedTask.admin_note" class="fs-8 text-gray-700 mt-1">
                                            "{{ selectedTask.admin_note }}"
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aksi review, cuma muncul kalau status "submitted" -->
                            <div v-if="selectedTask.status === 'submitted'" class="mt-5 pt-4 border-top">
                                <div v-if="reviewingId !== selectedTask.id" class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-sm btn-success" @click="openReview(selectedTask, 'accept')">
                                        <KTIcon icon-name="check" icon-class="fs-6 me-1" />
                                        Terima
                                    </button>
                                    <button class="btn btn-sm btn-warning" @click="openReview(selectedTask, 'revise')">
                                        <KTIcon icon-name="arrows-circle" icon-class="fs-6 me-1" />
                                        Revisi
                                    </button>
                                    <button class="btn btn-sm btn-danger" @click="openReview(selectedTask, 'reject')">
                                        <KTIcon icon-name="cross" icon-class="fs-6 me-1" />
                                        Tolak
                                    </button>
                                </div>
                                <div v-else class="border rounded p-3 bg-light">
                                    <div class="fw-semibold fs-8 mb-2">{{ reviewActionLabel[reviewAction!] }}</div>
                                    <textarea v-model="reviewNote" class="form-control form-control-sm mb-3" rows="2" :placeholder="reviewAction === 'accept' ? 'Catatan (opsional)' : 'Jelaskan alasannya ke intern...'"></textarea>
                                    <div v-if="reviewMsg" class="alert alert-danger py-2 fs-8 mb-3">{{ reviewMsg }}</div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-light" @click="closeReview">Batal</button>
                                        <button class="btn btn-sm btn-primary" :disabled="submittingReview" @click="submitReview(selectedTask)">
                                            <span v-if="submittingReview" class="spinner-border spinner-border-sm me-2"></span>
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- ══ MODAL FORM BERI TUGAS ══ -->
        <div v-if="showForm" class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Beri Tugas Baru</h5>
                        <button class="btn btn-sm btn-icon btn-light" @click="closeForm">
                            <KTIcon icon-name="cross" icon-class="fs-4" />
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Diberikan Kepada</label>
                            <div class="form-control form-control-solid bg-light-secondary">
                                {{ selectedUser?.name }} ({{ selectedUser?.email }})
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Judul Tugas</label>
                            <input v-model="form.title" type="text" class="form-control" placeholder="Misal: Buat laporan mingguan" />
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Deskripsi (opsional)</label>
                            <textarea v-model="form.description" class="form-control" rows="3" placeholder="Detail tugas..."></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Deadline (opsional)</label>
                            <input v-model="form.due_date" type="date" class="form-control" />
                        </div>
                        <div v-if="formMsg" class="alert alert-danger py-2 fs-7 mt-3">{{ formMsg }}</div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" @click="closeForm">Batal</button>
                        <button class="btn btn-primary" :disabled="saving" @click="submitForm">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                            Simpan
                        </button>
                    </div>
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

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import axios from '@/libs/axios'
import { toast } from 'vue3-toastify'

interface Attachment {
    id: number
    original_name: string
    url: string
    is_image: boolean
}

interface Task {
    id: number
    title: string
    description: string | null
    status: 'belum' | 'sedang' | 'submitted' | 'revisi' | 'selesai' | 'ditolak'
    due_date: string | null
    attachments: Attachment[]
    submission_note: string | null
    admin_note: string | null
    submitted_at: string | null
    reviewed_at?: string | null
    created_at?: string
    user?: { id: number; name: string; email: string }
    creator?: { id: number; name: string }
}

interface Intern {
    id: number
    name: string
    email: string
    photo?: string | null
}

const tasks = ref<Task[]>([])
const interns = ref<Intern[]>([])
const loading = ref(false)
const deletingId = ref<number | null>(null)
const downloadingZipId = ref<number | null>(null)
const previewImageUrl = ref<string | null>(null)
const searchQuery = ref('')
const showSearch = ref(false)
const sortBy = ref<'newest' | 'oldest' | 'deadline_asc' | 'deadline_desc'>('newest')

const selectedUserId = ref<number | null>(null)
const selectedTaskId = ref<number | null>(null)

const showForm = ref(false)
const saving = ref(false)
const formMsg = ref('')

const form = ref({
    title: '',
    description: '',
    due_date: '',
})

const reviewingId = ref<number | null>(null)
const reviewAction = ref<'accept' | 'reject' | 'revise' | null>(null)
const reviewNote = ref('')
const reviewMsg = ref('')
const submittingReview = ref(false)

const statusLabel: Record<string, string> = {
    belum: 'Belum Dikerjakan',
    sedang: 'Sedang Dikerjakan',
    submitted: 'Menunggu Review',
    revisi: 'Perlu Revisi',
    selesai: 'Selesai',
    ditolak: 'Ditolak',
}

const statusBadge: Record<string, string> = {
    belum: 'badge-light-warning',
    sedang: 'badge-light-primary',
    submitted: 'badge-light-info',
    revisi: 'badge-light-danger',
    selesai: 'badge-light-success',
    ditolak: 'badge-light-danger',
}

const statusColor: Record<string, string> = {
    belum: 'warning',
    sedang: 'primary',
    submitted: 'info',
    revisi: 'danger',
    selesai: 'success',
    ditolak: 'danger',
}

const taskBorderClass: Record<string, string> = {
    belum: 'border-warning',
    sedang: 'border-primary',
    submitted: 'border-info',
    revisi: 'border-danger',
    selesai: 'border-success',
    ditolak: 'border-danger',
}

const reviewIconBg: Record<string, string> = {
    selesai: 'bg-light-success text-success',
    revisi: 'bg-light-danger text-danger',
    ditolak: 'bg-light-danger text-danger',
}

const reviewActionLabel: Record<string, string> = {
    accept: 'Terima tugas ini?',
    reject: 'Tolak tugas ini — jelaskan alasannya',
    revise: 'Minta revisi — jelaskan apa yang perlu diperbaiki',
}

const filteredInterns = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase()
    if (!keyword) return interns.value
    return interns.value.filter(
        (u) => u.name?.toLowerCase().includes(keyword) || u.email?.toLowerCase().includes(keyword)
    )
})

const selectedUser = computed(() => interns.value.find((u) => u.id === selectedUserId.value) ?? null)

const userTasks = computed(() => {
    if (!selectedUserId.value) return []

    const list = tasks.value.filter((t) => t.user?.id === selectedUserId.value)

    return [...list].sort((a, b) => {
        if (sortBy.value === 'oldest') return new Date(a.created_at ?? 0).getTime() - new Date(b.created_at ?? 0).getTime()
        if (sortBy.value === 'deadline_asc') {
            const da = a.due_date ? new Date(a.due_date).getTime() : Number.MAX_SAFE_INTEGER
            const db = b.due_date ? new Date(b.due_date).getTime() : Number.MAX_SAFE_INTEGER
            return da - db
        }
        if (sortBy.value === 'deadline_desc') {
            const da = a.due_date ? new Date(a.due_date).getTime() : Number.MIN_SAFE_INTEGER
            const db = b.due_date ? new Date(b.due_date).getTime() : Number.MIN_SAFE_INTEGER
            return db - da
        }
        return new Date(b.created_at ?? 0).getTime() - new Date(a.created_at ?? 0).getTime()
    })
})

const selectedTask = computed(() => userTasks.value.find((t) => t.id === selectedTaskId.value) ?? null)

// Reset pilihan tugas kalau ganti user
watch(selectedUserId, () => {
    selectedTaskId.value = null
})

function taskCountFor(userId: number) {
    return tasks.value.filter((t) => t.user?.id === userId).length
}

function selectUser(userId: number) {
    selectedUserId.value = userId
}

function resolvePhotoUrl(path?: string | null): string {
    if (!path) return ''
    if (path.startsWith('http://') || path.startsWith('https://')) return path
    const base = (import.meta as any).env?.VITE_API_URL ?? ''
    const trimmed = path.replace(/^\/+/, '')
    const cleanPath = trimmed.startsWith('storage/') ? `/${trimmed}` : `/storage/${trimmed}`
    return `${base}${cleanPath}`
}

function imageAttachments(task: Task) {
    return (task.attachments ?? []).filter((a) => a.is_image)
}

function fileAttachments(task: Task) {
    return (task.attachments ?? []).filter((a) => !a.is_image)
}

function formatDate(dateStr: string | null) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function formatDateTime(dateStr: string | null | undefined) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function loadTasks() {
    loading.value = true
    try {
        const res = await axios.get('/admin/tasks')
        tasks.value = res.data?.data ?? []
    } catch (e) {
        console.error('Gagal memuat daftar tugas:', e)
    } finally {
        loading.value = false
    }
}

async function loadInterns() {
    try {
        const res = await axios.get('/admin/intern-periods')
        interns.value = res.data?.data ?? []
    } catch (e) {
        console.error('Gagal memuat daftar peserta magang:', e)
    }
}

function resetForm() {
    form.value = { title: '', description: '', due_date: '' }
    formMsg.value = ''
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openImagePreview(url: string) {
    previewImageUrl.value = url
}

function closeImagePreview() {
    previewImageUrl.value = null
}

function closeForm() {
    showForm.value = false
}

async function submitForm() {
    if (!selectedUserId.value) {
        formMsg.value = 'Pilih peserta magang dulu'
        return
    }
    if (!form.value.title.trim()) {
        formMsg.value = 'Judul tugas wajib diisi'
        return
    }

    saving.value = true
    formMsg.value = ''
    try {
        await axios.post('/admin/tasks', { ...form.value, user_id: selectedUserId.value })
        toast.success('Tugas berhasil diberikan')
        showForm.value = false
        await loadTasks()
    } catch (e: any) {
        formMsg.value = e.response?.data?.message ?? 'Gagal memberi tugas'
    } finally {
        saving.value = false
    }
}

async function removeTask(task: Task) {
    if (!confirm(`Hapus tugas "${task.title}"?`)) return
    deletingId.value = task.id
    try {
        await axios.delete(`/admin/tasks/${task.id}`)
        toast.success('Tugas berhasil dihapus')
        if (selectedTaskId.value === task.id) selectedTaskId.value = null
        await loadTasks()
    } catch (e: any) {
        toast.error(e.response?.data?.message ?? 'Gagal menghapus tugas')
    } finally {
        deletingId.value = null
    }
}

function downloadZip(task: Task) {
    downloadingZipId.value = task.id
    axios
        .get(`/tasks/${task.id}/attachments/zip`, { responseType: 'blob' })
        .then((res) => {
            const url = window.URL.createObjectURL(new Blob([res.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `tugas-${task.id}-lampiran.zip`)
            document.body.appendChild(link)
            link.click()
            link.remove()
        })
        .catch(() => {
            toast.error('Gagal mengunduh lampiran')
        })
        .finally(() => {
            downloadingZipId.value = null
        })
}

function openReview(task: Task, action: 'accept' | 'reject' | 'revise') {
    reviewingId.value = task.id
    reviewAction.value = action
    reviewNote.value = ''
    reviewMsg.value = ''
}

function closeReview() {
    reviewingId.value = null
    reviewAction.value = null
}

async function submitReview(task: Task) {
    if (reviewAction.value !== 'accept' && !reviewNote.value.trim()) {
        reviewMsg.value = 'Catatan wajib diisi biar intern tau apa yang perlu diperbaiki'
        return
    }

    submittingReview.value = true
    reviewMsg.value = ''
    try {
        await axios.post(`/admin/tasks/${task.id}/review`, {
            action: reviewAction.value,
            admin_note: reviewNote.value || null,
        })
        toast.success('Review berhasil disimpan')
        reviewingId.value = null
        await loadTasks()
    } catch (e: any) {
        reviewMsg.value = e.response?.data?.message ?? 'Gagal menyimpan review'
    } finally {
        submittingReview.value = false
    }
}

onMounted(() => {
    loadTasks()
    loadInterns()
})
</script>