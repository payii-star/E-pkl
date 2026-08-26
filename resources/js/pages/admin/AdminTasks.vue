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
                    <div v-else class="table-responsive">
                        <table class="table table-row-bordered align-middle">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Judul</th>
                                    <th>Diberikan Ke</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Diberikan Oleh</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="task in tasks" :key="task.id">
                                    <td>
                                        <div class="fw-bold text-gray-800">{{ task.title }}</div>
                                        <div v-if="task.description" class="text-muted fs-8 text-truncate" style="max-width:280px">
                                            {{ task.description }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ task.user?.name ?? '-' }}</div>
                                        <div class="text-muted fs-8">{{ task.user?.email ?? '' }}</div>
                                    </td>
                                    <td>{{ formatDate(task.due_date) }}</td>
                                    <td>
                                        <span class="badge" :class="statusBadge[task.status]">
                                            {{ statusLabel[task.status] }}
                                        </span>
                                    </td>
                                    <td class="fs-7 text-muted">{{ task.creator?.name ?? '-' }}</td>
                                    <td class="text-end">
                                        <button
                                            class="btn btn-icon btn-sm btn-light-danger"
                                            :disabled="deletingId === task.id"
                                            @click="removeTask(task)"
                                        >
                                            <span v-if="deletingId === task.id" class="spinner-border spinner-border-sm"></span>
                                            <KTIcon v-else icon-name="trash" icon-class="fs-6" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ MODAL FORM ══ -->
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
    status: 'belum' | 'sedang' | 'selesai'
    due_date: string | null
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

const statusLabel: Record<string, string> = {
    belum: 'Belum Dikerjakan',
    sedang: 'Sedang Dikerjakan',
    selesai: 'Selesai',
}

const statusBadge: Record<string, string> = {
    belum: 'badge-light-warning',
    sedang: 'badge-light-primary',
    selesai: 'badge-light-success',
}

function formatDate(dateStr: string | null) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
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

onMounted(() => {
    loadTasks()
    loadInterns()
})
</script>