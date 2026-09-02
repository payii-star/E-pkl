<template>
    <div class="row g-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Atur Periode Magang</h2>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div v-if="loading" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div v-else-if="interns.length === 0" class="text-center text-muted py-10">
                        Belum ada peserta magang.
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-row-bordered align-middle">
                            <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th style="width:60px">Foto</th>
                                    <th>Nama</th>
                                    <th>Periode Magang</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="intern in interns" :key="intern.id">
                                    <td>
                                        <div class="symbol symbol-40px">
                                            <img v-if="intern.photo" :src="resolvePhotoUrl(intern.photo)" class="rounded" />
                                            <span v-else class="symbol-label bg-light-primary text-primary fw-bold">
                                                {{ intern.name?.charAt(0)?.toUpperCase() }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-gray-800">{{ intern.name }}</div>
                                        <div class="text-muted fs-8">{{ intern.email }}</div>
                                    </td>
                                    <td>
                                        <span v-if="intern.tanggal_mulai && intern.tanggal_selesai" class="fs-7">
                                            {{ formatDate(intern.tanggal_mulai) }} &mdash; {{ formatDate(intern.tanggal_selesai) }}
                                        </span>
                                        <span v-else class="badge badge-light-warning">Belum diatur</span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light-primary" @click="openEdit(intern)">
                                            <KTIcon icon-name="calendar" icon-class="fs-6 me-1" />
                                            Atur Periode
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
                        <h5 class="modal-title fw-bold">Periode Magang — {{ editing?.name }}</h5>
                        <button class="btn btn-sm btn-icon btn-light" @click="closeForm">
                            <KTIcon icon-name="cross" icon-class="fs-4" />
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tanggal Mulai</label>
                            <input v-model="form.tanggal_mulai" type="date" class="form-control" />
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Tanggal Selesai</label>
                            <input v-model="form.tanggal_selesai" type="date" class="form-control" />
                        </div>
                        <div v-if="formMsg" class="alert alert-danger py-2 fs-7">{{ formMsg }}</div>
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

const interns = ref<any[]>([])
const loading = ref(false)

const showForm = ref(false)
const editing  = ref<any>(null)
const saving   = ref(false)
const formMsg  = ref('')

const form = ref({
    tanggal_mulai: '',
    tanggal_selesai: '',
})

/**
 * Ubah path foto dari API jadi full URL ke backend Laravel.
 * Cek dulu apakah path sudah mengandung "storage/" sebelum
 * menambahkannya, supaya tidak jadi dobel ("/storage/storage/...").
 */
function resolvePhotoUrl(path?: string | null): string {
    if (!path) return ''

    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path
    }

    const base = import.meta.env.VITE_API_URL ?? ''

    // Buang leading slash biar gampang dicek
    const trimmed = path.replace(/^\/+/, '')

    // Kalau path udah mengandung "storage/" di depan, jangan ditambah lagi
    const cleanPath = trimmed.startsWith('storage/') ? `/${trimmed}` : `/storage/${trimmed}`

    return `${base}${cleanPath}`
}

function formatDate(dateStr: string) {
    if (!dateStr) return '-'
    return new Date(dateStr).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

async function loadInterns() {
    loading.value = true
    try {
        const res = await axios.get('/admin/intern-periods')
        interns.value = res.data?.data ?? []
    } catch (e) {
        console.error('Gagal memuat daftar peserta magang:', e)
    } finally {
        loading.value = false
    }
}

function openEdit(intern: any) {
    editing.value = intern
    form.value = {
        tanggal_mulai: intern.tanggal_mulai ? intern.tanggal_mulai.slice(0, 10) : '',
        tanggal_selesai: intern.tanggal_selesai ? intern.tanggal_selesai.slice(0, 10) : '',
    }
    formMsg.value = ''
    showForm.value = true
}

function closeForm() {
    showForm.value = false
}

async function submitForm() {
    if (!form.value.tanggal_mulai || !form.value.tanggal_selesai) {
        formMsg.value = 'Tanggal mulai dan selesai wajib diisi'
        return
    }
    saving.value = true
    formMsg.value = ''
    try {
        await axios.put(`/admin/intern-periods/${editing.value.id}`, form.value)
        toast.success('Periode magang berhasil disimpan')
        showForm.value = false
        await loadInterns()
    } catch (e: any) {
        formMsg.value = e.response?.data?.message ?? 'Gagal menyimpan periode magang'
    } finally {
        saving.value = false
    }
}

onMounted(loadInterns)
</script>