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
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="!journals.length">
                        <td colspan="4" class="text-center text-gray-500">Belum ada jurnal</td>
                    </tr>
                    <tr v-for="(journal, i) in journals" :key="journal.id">
                        <td>{{ i + 1 }}</td>
                        <td>{{ formatDate(journal.date) }}</td>
                        <td>
                            <div v-for="act in journal.activities" :key="act.id" class="mb-2">
                                <span class="fw-bold">{{ act.jam_mulai }} - {{ act.jam_selesai }}</span>
                                <div class="text-gray-700">{{ act.kegiatan }}</div>
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
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue';
import ApiService from '@/core/services/ApiService';

export default defineComponent({
    setup() {
        const journals = ref<any[]>([]);

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

        onMounted(fetchHistory);

        return { journals, formatDate, statusLabel };
    },
});
</script>
