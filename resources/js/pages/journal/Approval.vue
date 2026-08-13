<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Approval Jurnal</h2>
            </div>
        </div>
        <div class="card-body">
            <!--begin::Tabs (tombol solid, bukan nav-tabs default)-->
            <div class="d-flex gap-2 mb-5">
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="activeTab === 'pending' ? 'btn-primary' : 'btn-light text-gray-600'"
                    @click="switchTab('pending')"
                >
                    Menunggu Approval
                </button>
                <button
                    type="button"
                    class="btn btn-sm"
                    :class="activeTab === 'history' ? 'btn-primary' : 'btn-light text-gray-600'"
                    @click="switchTab('history')"
                >
                    Riwayat Approval
                </button>
            </div>
            <!--end::Tabs-->

            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                        <tr>
                            <th class="text-nowrap">Nama Karyawan</th>
                            <th class="text-nowrap">Tanggal</th>
                            <th class="text-nowrap">Ringkasan</th>
                            <th v-if="activeTab === 'history'" class="text-nowrap">Status</th>
                            <th v-if="activeTab === 'history'" class="text-nowrap">Catatan</th>
                            <th class="text-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="text-center text-gray-500">Memuat data...</td>
                        </tr>
                        <tr v-else-if="!journals.length">
                            <td colspan="6" class="text-center text-gray-500">
                                {{ activeTab === 'pending' ? 'Tidak ada jurnal yang menunggu approval' : 'Belum ada riwayat approval' }}
                            </td>
                        </tr>
                        <template v-for="journal in journals" :key="journal.id">
                            <tr>
                                <td class="text-nowrap">{{ journal.user?.name }}</td>
                                <td class="text-nowrap">{{ formatDate(journal.date) }}</td>
                                <td style="min-width: 220px">
                                    <div v-for="act in journal.activities" :key="act.id" class="mb-1">
                                        <span class="fw-bold text-nowrap">{{ act.jam_mulai }}–{{ act.jam_selesai }}</span>
                                        <span class="text-gray-600"> — {{ truncate(act.kegiatan) }}</span>
                                    </div>
                                </td>
                                <td v-if="activeTab === 'history'" class="text-nowrap">
                                    <span
                                        class="badge"
                                        :class="{
                                            'badge-light-success': journal.status === 'approved',
                                            'badge-light-danger': journal.status === 'rejected',
                                        }"
                                    >
                                        {{ journal.status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                                    </span>
                                </td>
                                <!--begin::Catatan - sekarang dipotong pakai truncate()-->
                                <td v-if="activeTab === 'history'" style="min-width: 160px">
                                    <span v-if="journal.catatan_approval" :class="journal.status === 'rejected' ? 'text-danger' : 'text-gray-700'">
                                        {{ truncate(journal.catatan_approval) }}
                                    </span>
                                    <span v-else class="text-gray-500 fst-italic">-</span>
                                </td>
                                <!--end::Catatan-->
                                <td class="text-nowrap">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-light-primary" @click="openDetail(journal)">Detail</button>
                                        <template v-if="activeTab === 'pending'">
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
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="rejectingId === journal.id">
                                <td colspan="6" class="bg-light-danger">
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

        <div v-if="selected" class="modal-backdrop-custom" @click.self="selected = null">
            <div class="modal-box-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">{{ selected.user?.name }} — {{ formatDate(selected.date) }}</h3>
                    <button class="btn btn-sm btn-icon btn-light" @click="selected = null">✕</button>
                </div>
                <div v-for="act in selected.activities" :key="act.id" class="mb-4 p-3 border rounded">
                    <div class="fw-bold mb-1">{{ act.jam_mulai }} - {{ act.jam_selesai }}</div>
                    <div class="text-gray-700">{{ act.kegiatan }}</div>
                </div>
                <div v-if="selected.foto" class="mb-2">
                    <label class="form-label d-block">Foto Kegiatan</label>
                    <img :src="fotoUrl(selected.foto)" class="rounded" style="max-width: 100%; max-height: 400px" />
                </div>
                <div v-if="selected.catatan_approval" class="mt-3">
                    <label class="form-label d-block">Catatan Pembimbing</label>
                    <div :class="selected.status === 'rejected' ? 'text-danger' : 'text-gray-700'">
                        {{ selected.catatan_approval }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApiService from '@/core/services/ApiService';

const activeTab = ref<'pending' | 'history'>('pending');
const journals = ref<any[]>([]);
const loading = ref(false);
const errorMessage = ref('');
const processingId = ref<number | null>(null);
const rejectingId = ref<number | null>(null);
const rejectNote = ref('');
const selected = ref<any>(null);

const truncate = (text: string, len = 15) =>
    text && text.length > len ? text.slice(0, len) + '.........' : text;

const fetchData = async () => {
    loading.value = true;
    errorMessage.value = '';
    try {
        const url = activeTab.value === 'pending' ? '/journals/pending-approval' : '/journals/approval-history';
        const { data } = await ApiService.get(url);
        journals.value = data.data ?? data;
    } catch (e: any) {
        errorMessage.value = e?.response?.data?.message ?? 'Gagal memuat data.';
    } finally {
        loading.value = false;
    }
};

const switchTab = (tab: 'pending' | 'history') => {
    activeTab.value = tab;
    fetchData();
};

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

const openDetail = (journal: any) => {
    selected.value = journal;
};

const fotoUrl = (path: string) => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return path.startsWith('/storage/') ? path : `/storage/${path.replace(/^\/+/, '')}`;
};

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
        await ApiService.post(`/journals/${journal.id}/reject`, { catatan_approval: rejectNote.value });
        journals.value = journals.value.filter((j) => j.id !== journal.id);
        rejectingId.value = null;
    } catch (e: any) {
        errorMessage.value = e?.response?.data?.message ?? 'Gagal menolak jurnal.';
    } finally {
        processingId.value = null;
    }
};

onMounted(fetchData);
</script>

<style scoped>
.modal-backdrop-custom {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}
.modal-box-custom {
    background: var(--bs-body-bg, #1e1e2d);
    border-radius: 8px;
    padding: 24px;
    width: 90%;
    max-width: 600px;
    max-height: 85vh;
    overflow-y: auto;
}
</style>