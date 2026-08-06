<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Jurnal Harian</h2>
            </div>
        </div>
        <div class="card-body">
            <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
            <div v-if="errorMessage" class="alert alert-danger">{{ errorMessage }}</div>

            <div class="mb-5">
                <label class="form-label">Tanggal</label>
                <input type="date" class="form-control w-md-300px" v-model="date" />
            </div>

            <label class="form-label">Aktivitas Hari Ini</label>

            <div
                v-for="(activity, index) in activities"
                :key="index"
                class="d-flex align-items-start gap-3 mb-4 p-4 border rounded"
            >
                <div class="d-flex gap-2">
                    <div>
                        <label class="form-label fs-8">Jam Mulai</label>
                        <input type="time" class="form-control" v-model="activity.jam_mulai" />
                    </div>
                    <div>
                        <label class="form-label fs-8">Jam Selesai</label>
                        <input type="time" class="form-control" v-model="activity.jam_selesai" />
                    </div>
                </div>
                <div class="flex-grow-1">
                    <label class="form-label fs-8">Kegiatan</label>
                    <textarea
                        class="form-control"
                        rows="2"
                        v-model="activity.kegiatan"
                        placeholder="Deskripsi kegiatan..."
                    ></textarea>
                </div>
                <button
                    type="button"
                    class="btn btn-icon btn-sm btn-light-danger mt-8"
                    @click="removeActivity(index)"
                    :disabled="activities.length === 1"
                >
                    <i class="ki-duotone ki-trash fs-3"></i>
                </button>
            </div>

            <button type="button" class="btn btn-light-primary mb-6" @click="addActivity">
                + Tambah Aktivitas
            </button>

            <div>
                <button type="button" class="btn btn-primary" :disabled="loading" @click="submit">
                    {{ loading ? 'Mengirim...' : 'Kirim Jurnal' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import ApiService from '@/core/services/ApiService';

interface Activity {
    jam_mulai: string;
    jam_selesai: string;
    kegiatan: string;
}

export default defineComponent({
    setup() {
        const today = new Date().toISOString().slice(0, 10);
        const date = ref(today);
        const activities = ref<Activity[]>([{ jam_mulai: '', jam_selesai: '', kegiatan: '' }]);
        const loading = ref(false);
        const successMessage = ref('');
        const errorMessage = ref('');

        const addActivity = () => {
            activities.value.push({ jam_mulai: '', jam_selesai: '', kegiatan: '' });
        };

        const removeActivity = (index: number) => {
            activities.value.splice(index, 1);
        };

        const submit = async () => {
            errorMessage.value = '';
            successMessage.value = '';
            loading.value = true;

            try {
                await ApiService.post('/journals', {
                    date: date.value,
                    activities: activities.value,
                });

                successMessage.value = 'Jurnal berhasil dikirim, menunggu approval atasan.';
                activities.value = [{ jam_mulai: '', jam_selesai: '', kegiatan: '' }];
                date.value = today;
            } catch (e: any) {
                errorMessage.value = e?.response?.data?.message ?? 'Gagal mengirim jurnal.';
            } finally {
                loading.value = false;
            }
        };

        return { date, activities, loading, successMessage, errorMessage, addActivity, removeActivity, submit };
    },
});
</script>
