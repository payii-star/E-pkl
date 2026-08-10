<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Approval Jurnal</h2>
            </div>
        </div>
        <div class="card-body">
            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <table class="table table-row-bordered align-middle">
                <thead>
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Tanggal</th>
                        <th>Ringkasan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading">
                        <td colspan="4" class="text-center text-gray-500">Memuat data...</td>
                    </tr>
                    <tr v-else-if="!journals.length">
                        <td colspan="4" class="text-center text-gray-500">Tidak ada jurnal yang menunggu approval</td>
                    </tr>
                    <template v-for="journal in journals" :key="journal.id">
                        <tr>
                            <td>{{ journal.user?.name }}</td>
                            <td>{{ formatDate(journal.date) }}</td>
                            <td>
                                <div v-for="act in journal.activities" :key="act.id" class="mb-1">
                                    <span class="fw-bold">{{ act.jam_mulai }}–{{ act.jam_selesai }}</span>
                                    <span class="text-gray-600"> — {{ act.kegiatan }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button
                                        class="btn btn-sm btn-light-success"
                                        :disabled="processingId === journal.id"
                                        @click="approve(journal)"
                                    >
                                        Setuju
                                    </button>
                                    <button
                                        class="btn btn-sm btn-light-danger"
                                        :disabled="processingId === journal.id"
                                        @click="toggleReject(journal.id)"
                                    >
                                        Tolak
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="rejectingId === journal.id">
                            <td colspan="4" class="bg-light-danger">
                                <div class="d-flex gap-2 align-items-start py-2">
                                    <textarea
                                        v-model="rejectNote"
                                        class="form-control form-control-sm"
                                        rows="2"
                                        placeholder="Catatan penolakan (wajib diisi)..."
                                    ></textarea>
                                    <button
                                        class="btn btn-sm btn-danger"
                                        :disabled="!rejectNote.trim() || processingId === journal.id"
                                        @click="reject(journal)"
                                    >
                                        Kirim Penolakan
                                    </button>
                                    <button class="btn btn-sm btn-light" @click="toggleReject(null)">Batal</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApiService from '@/core/services/ApiService';

const journals = ref<any[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const processingId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);
const rejectNote = ref('');

const fetchPending = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const { data } = await ApiService.get('/journals/pending-approval');
        journals.value = data.data ?? data;
    } catch (e: any) {
        errorMessage.value = e?.response?.data?.message ?? 'Gagal memuat data approval.';
    } finally {
        loading.value = false;
    }
};

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const approve = async (journal: any) => {
    processingId.value = journal.id;
    try {
        await ApiService.post(`/journals/${journal.id}/approve`, {});
        journals.value = journals.value.filter((j) => j.id !== journal.id);
    } catch (e: any) {
        errorMessage.value = e?.response?.data?.message ?? 'Gagal menyetujui jurnal.';
    } finally {
        processingId.value = null;
    }
};

const toggleReject = (id: number | null) => {
    rejectingId.value = rejectingId.value === id ? null : id;
    rejectNote.value = '';
};

const reject = async (journal: any) => {
    processingId.value = journal.id;
    try {
        await ApiService.post(`/journals/${journal.id}/reject`, {
            catatan_approval: rejectNote.value,
        });
        journals.value = journals.value.filter((j) => j.id !== journal.id);
        rejectingId.value = null;
    } catch (e: any) {
        errorMessage.value = e?.response?.data?.message ?? 'Gagal menolak jurnal.';
    } finally {
        processingId.value = null;
    }
};

onMounted(fetchPending);
</script>