<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Kelola Tugas</h2>
                    </div>
                    <div class="card-toolbar">
                        <button class="btn btn-primary btn-sm" @click="openCreate">
                            <KTIcon icon-name="plus" icon-class="fs-6 me-1" />
                            Beri Tugas
                        </button>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div v-else-if="tasks.length === 0" class="text-center text-muted py-10">
                        Belum ada tugas yang diberikan.
                    </div>
                    <div v-else class="d-flex flex-column gap-4">
                        <div v-for="task in tasks" :key="task.id" class="border rounded p-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <div class="fw-bold text-gray-800">{{ task.title }}</div>
                                    <div class="text-muted fs-8">
                                        Untuk: <b>{{ task.user?.name ?? '-' }}</b> ({{ task.user?.email ?? '' }})
                                    </div>
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

                            <!-- File yang dikumpulkan intern -->
                            <div v-if="task.attachment_url" class="bg-light-secondary bg-opacity-25 rounded p-3 mb-3">
                                <a :href="task.attachment_url" target="_blank" class="btn btn-sm btn-light-primary mb-2">
                                    <KTIcon icon-name="paper-clip" icon-class="fs-6 me-1" />
                                    Lihat File Terkumpul
                                </a>
                                <div v-if="task.submission_note" class="text-muted fs-8">
                                    Catatan intern: {{ task.submission_note }}
                                </div>
                                <div v-if="task.submitted_at" class="text-muted fs-8">
                                    Dikumpulkan: {{ formatDateTime(task.submitted_at) }}
                                </div>
                            </div>

                            <div v-if="task.admin_note && task.status !== 'submitted'" class="text-muted fs-8 mb-3">
                                <b>Catatan review:</b> {{ task.admin_note }}
                            </div>

                            <!-- Aksi review, cuma muncul kalau statusnya "submitted" -->
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
                                    <textarea
                                        v-model="reviewNote"
                                        class="form-control form-control-sm mb-3"
                                        rows="2"
                                        :placeholder="reviewAction === 'accept' ? 'Catatan (opsional)' : 'Jelaskan alasannya ke intern...'"
                                    ></textarea>
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
                            <select v-model="form.user_id" class="form-select">
                                <option :value="null" disabled>Pilih peserta magang...</option>
                                <option v-for="u in interns" :key="u.id" :value="u.id">
                                    {{ u.name }} ({{ u.email }})
                                </option>
                            </select>
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
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from '@/libs/axios'
import { toast } from 'vue3-toastify'

interface Task {
    id: number
    title: string
    description: string | null
    status: 'belum' | 'sedang' | 'submitted' | 'revisi' | 'selesai' | 'ditolak'
    due_date: string | null
    attachment_url: string | null
    submission_note: string | null
    admin_note: string | null
    submitted_at: string | null
    user?: { id: number; name: string; email: string }
    creator?: { id: number; name: string }
}

const tasks = ref<Task[]>([])
const interns = ref<any[]>([])
const loading = ref(false)
const deletingId = ref<number | null>(null)

const showForm = ref(false)
const saving = ref(false)
const formMsg = ref('')

const form = ref({
    user_id: null as number | null,
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
    form.value = { user_id: null, title: '', description: '', due_date: '' }
    formMsg.value = ''
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function closeForm() {
    showForm.value = false
}

async function submitForm() {
    if (!form.value.user_id) {
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
        await axios.post('/admin/tasks', form.value)
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