<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>Riwayat Jurnal</h2></div>
        </div>
        <div class="card-body">
            <table class="table table-row-bordered align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Breakdown Aktivitas</th>
                        <th>Status</th>
                        <th>Catatan Pembimbing</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!journals.length">
                        <td colspan="6" class="text-center text-gray-500">Belum ada jurnal</td>
                    </tr>
                    <tr v-for="(journal, i) in journals" :key="journal.id">
                        <td>{{ i + 1 }}</td>
                        <td>{{ formatDate(journal.date) }}</td>
                        <td>
                            <div v-for="act in journal.activities" :key="act.id" class="mb-2">
                                <span class="fw-bold">{{ act.jam_mulai }} - {{ act.jam_selesai }}</span>
                                <div class="text-gray-700">{{ truncate(act.kegiatan) }}</div>
                            </div>
                        </td>
                        <td>
                            <span
                                class="badge"
                                :class="{
                                    'badge-light-warning': journal.status === 'pending',
                                    'badge-light-success': journal.status === 'approved',
                                    'badge-light-danger': journal.status === 'rejected',
                                }"
                            >
                                {{ statusLabel(journal.status) }}
                            </span>
                        </td>
                        <td>
                            <span v-if="journal.catatan_approval" :class="journal.status === 'rejected' ? 'text-danger' : 'text-gray-700'">
                                {{ truncate(journal.catatan_approval) }}
                            </span>
                            <span v-else class="text-gray-500 fst-italic">
                                {{ journal.status === 'pending' ? 'Menunggu diperiksa' : 'Tidak ada catatan' }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-light-primary" @click="openDetail(journal)">Detail</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="selected" class="modal-backdrop-custom" @click.self="selected = null">
            <div class="modal-box-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Detail Jurnal — {{ formatDate(selected.date) }}</h3>
                    <button class="btn btn-sm btn-icon btn-light" @click="selected = null">✕</button>
                </div>

                <div v-for="act in selected.activities" :key="act.id" class="mb-4 p-3 border rounded">
                    <div class="fw-bold mb-1">{{ act.jam_mulai }} - {{ act.jam_selesai }}</div>
                    <div class="text-gray-700">{{ act.kegiatan }}</div>
                </div>

                <div v-if="selected.foto" class="mb-4">
                    <label class="form-label d-block">Foto Kegiatan</label>
                    <img :src="fotoUrl(selected.foto)" class="rounded" style="max-width: 100%; max-height: 400px" />
                </div>

                <div class="mb-2">
                    <span
                        class="badge"
                        :class="{
                            'badge-light-warning': selected.status === 'pending',
                            'badge-light-success': selected.status === 'approved',
                            'badge-light-danger': selected.status === 'rejected',
                        }"
                    >
                        {{ statusLabel(selected.status) }}
                    </span>
                </div>
                <div class="text-gray-700">
                    <strong>Catatan Pembimbing:</strong> {{ selected.catatan_approval || 'Tidak ada catatan' }}
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue';
import ApiService from '@/core/services/ApiService';

export default defineComponent({
    setup() {
        const journals = ref<any[]>([]);
        const selected = ref<any>(null);

        const fetchHistory = async () => {
            try {
                const { data } = await ApiService.get('/journals/history');
                journals.value = data.data;
            } catch (e) {
                console.error(e);
            }
        };

        const formatDate = (d: string) =>
            new Date(d).toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });

        const statusLabel = (s: string) =>
            ({ pending: 'Belum Diverifikasi', approved: 'Disetujui', rejected: 'Ditolak' }[s] ?? s);

        const truncate = (text: string, len = 15) =>
            text && text.length > len ? text.slice(0, len) + '.........' : text;

        const fotoUrl = (path: string) => {
            if (!path) return '';
            if (path.startsWith('http://') || path.startsWith('https://')) return path;
            return path.startsWith('/storage/') ? path : `/storage/${path.replace(/^\/+/, '')}`;
        };

        const openDetail = (journal: any) => {
            selected.value = journal;
        };

        onMounted(fetchHistory);

        return { journals, selected, formatDate, statusLabel, truncate, fotoUrl, openDetail };
    },
});
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