<template>
    <div class="row g-5">
        <div class="col-12">
            <!-- ══ DAFTAR USER ══ -->
            <div class="card mb-5">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Kelola Tugas per User</h2>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex align-items-center gap-2 bg-light rounded px-3 py-2">
                            <KTIcon icon-name="magnifier" icon-class="fs-6 text-muted" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="form-control form-control-sm border-0 bg-transparent shadow-none"
                                placeholder="Cari nama..."
                                style="min-width: 200px;"
                            />
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div v-else-if="!filteredInterns.length" class="text-center text-muted py-10">
                        Tidak ada user yang cocok dengan pencarian.
                    </div>
                    <div v-else class="d-flex flex-wrap gap-3">
                        <div
                            v-for="u in filteredInterns"
                            :key="u.id"
                            role="button"
                            tabindex="0"
                            class="border rounded p-4 text-center"
                            :class="selectedUserId === u.id ? 'border-primary bg-light-primary' : ''"
                            style="width: 140px; cursor: pointer;"
                            @click="selectUser(u.id)"
                        >
                            <div class="symbol symbol-50px mb-2 mx-auto">
                                <img v-if="u.photo" :src="resolvePhotoUrl(u.photo)" class="rounded" />
                                <span v-else class="symbol-label bg-light-primary text-primary fw-bold">
                                    {{ u.name?.charAt(0)?.toUpperCase() }}
                                </span>
                            </div>
                            <div class="fw-semibold fs-8 text-truncate">{{ u.name }}</div>
                            <span class="badge badge-light-success fs-9 mt-1">{{ taskCountFor(u.id) }}x</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ PLACEHOLDER: BELUM PILIH USER ══ -->
            <div v-if="!selectedUserId" class="card">
                <div class="card-body text-center py-20 text-muted">
                    <KTIcon icon-name="user" icon-class="fs-3x mb-3 d-block" />
                    <div class="fs-5 fw-semibold mb-1">Pilih user</div>
                    <div class="fs-7">Klik salah satu nama di atas untuk melihat & kelola tugasnya</div>
                </div>
            </div>

            <!-- ══ DETAIL TUGAS USER TERPILIH ══ -->
            <div v-else class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title d-flex align-items-center gap-3">
                        <div class="symbol symbol-45px">
                            <img v-if="selectedUser?.photo" :src="resolvePhotoUrl(selectedUser.photo)" class="rounded" />
                            <span v-else class="symbol-label bg-light-primary text-primary fw-bold">
                                {{ selectedUser?.name?.charAt(0)?.toUpperCase() }}
                            </span>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-0">{{ selectedUser?.name }}</h2>
                            <div class="text-muted fs-8">{{ selectedUser?.email }}</div>
                        </div>
                    </div>
                    <div class="card-toolbar d-flex gap-2 align-items-center flex-wrap">
                        <select v-model="sortBy" class="form-select form-select-sm" style="width: 185px;">
                            <option value="newest">Newest</option>
                            <option value="oldest">Oldest</option>
                            <option value="deadline_asc">Deadline terdekat</option>
                            <option value="deadline_desc">Deadline terjauh</option>
                        </select>
                        <button class="btn btn-primary btn-sm" @click="openCreate">
                            <KTIcon icon-name="plus" icon-class="fs-6 me-1" />
                            Beri Tugas
                        </button>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div v-if="userTasks.length === 0" class="text-center text-muted py-10">
                        Belum ada tugas untuk user ini.
                    </div>
                    <div v-else class="d-flex flex-column gap-3">
                        <div v-for="task in userTasks" :key="task.id" class="border rounded p-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <div class="fw-bold text-gray-800">{{ task.title }}</div>
                                    <div v-if="task.due_date" class="text-muted fs-8">Deadline: {{ formatDate(task.due_date) }}</div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" :class="statusBadge[task.status]">{{ statusLabel[task.status] }}</span>
                                    <button
                                        class="btn btn-icon btn-sm btn-light-danger"
                                        :disabled="deletingId === task.id"
                                        @click="removeTask(task)"
                                    >
                                        <span v-if="deletingId === task.id" class="spinner-border spinner-border-sm"></span>
                                        <KTIcon v-else icon-name="trash" icon-class="fs-6" />
                                    </button>
                                </div>
                            </div>

                            <p v-if="task.description" class="text-muted fs-7 mb-3">{{ task.description }}</p>

                            <div v-if="task.attachments && task.attachments.length" class="bg-light-secondary bg-opacity-25 rounded p-3 mb-3">
                                <div v-if="imageAttachments(task).length" class="mb-3" :style="imageAttachments(task).length > 5 ? 'display:grid; grid-template-columns: repeat(auto-fill, minmax(90px, 110px)); gap:0.5rem;' : 'display:grid; grid-template-columns: repeat(auto-fill, minmax(110px, 140px)); gap:0.5rem;'">
                                    <div v-for="img in imageAttachments(task)" :key="img.id">
                                        <div role="button" tabindex="0" class="card border-0 overflow-hidden shadow-sm h-100" style="cursor:pointer; user-select:none;" @click="openImagePreview(img.url)" @keydown.enter.prevent="openImagePreview(img.url)" @keydown.space.prevent="openImagePreview(img.url)">
                                            <img :src="img.url" class="w-100" style="aspect-ratio:1/1; object-fit:cover; display:block;" />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="fileAttachments(task).length" class="mb-2">
                                    <div v-for="f in fileAttachments(task)" :key="f.id" class="d-flex align-items-center gap-2 fs-8 text-muted mb-1">
                                        <KTIcon icon-name="file" icon-class="fs-7" />
                                        {{ f.original_name }}
                                    </div>
                                </div>

                                <button class="btn btn-sm btn-light-primary mb-2" :disabled="downloadingZipId === task.id" @click="downloadZip(task)">
                                    <span v-if="downloadingZipId === task.id" class="spinner-border spinner-border-sm me-2"></span>
                                    <KTIcon v-else icon-name="folder-down" icon-class="fs-6 me-1" />
                                    Download Semua Lampiran (ZIP)
                                </button>

                                <div v-if="task.submission_note" class="text-muted fs-8">Catatan intern: {{ task.submission_note }}</div>
                                <div v-if="task.submitted_at" class="text-muted fs-8">Dikumpulkan: {{ formatDateTime(task.submitted_at) }}</div>
                            </div>

                            <div v-if="task.admin_note && task.status !== 'submitted'" class="text-muted fs-8 mb-3">
                                <b>Catatan review:</b> {{ task.admin_note }}
                            </div>

                            <div v-if="task.status === 'submitted'">
                                <div v-if="reviewingId !== task.id" class="d-flex gap-2">
                                    <button class="btn btn-sm btn-success" @click="openReview(task, 'accept')">
                                        <KTIcon icon-name="check" icon-class="fs-6 me-1" />
                                        Terima
                                    </button>
                                    <button class="btn btn-sm btn-warning" @click="openReview(task, 'revise')">
                                        <KTIcon icon-name="arrows-circle" icon-class="fs-6 me-1" />
                                        Minta Revisi
                                    </button>
                                    <button class="btn btn-sm btn-danger" @click="openReview(task, 'reject')">
                                        <KTIcon icon-name="cross" icon-class="fs-6 me-1" />
                                        Tolak
                                    </button>
                                </div>
                                <div v-else class="border rounded p-3 bg-light">
                                    <div class="fw-semibold fs-7 mb-2">{{ reviewActionLabel[reviewAction!] }}</div>
                                    <textarea v-model="reviewNote" class="form-control form-control-sm mb-3" rows="2" :placeholder="reviewAction === 'accept' ? 'Catatan (opsional)' : 'Jelaskan alasannya ke intern...'"></textarea>
                                    <div v-if="reviewMsg" class="alert alert-danger py-2 fs-7 mb-3">{{ reviewMsg }}</div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-light" @click="closeReview">Batal</button>
                                        <button class="btn btn-sm btn-primary" :disabled="submittingReview" @click="submitReview(task)">
                                            <span v-if="submittingReview" class="spinner-border spinner-border-sm me-2"></span>
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
import { ref, onMounted, computed } from 'vue'
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
const sortBy = ref<'newest' | 'oldest' | 'deadline_asc' | 'deadline_desc'>('newest')

const selectedUserId = ref<number | null>(null)

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

function formatDateTime(dateStr: string | null) {
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