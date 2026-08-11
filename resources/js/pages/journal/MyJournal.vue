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

            <div class="mb-6">
                <label class="form-label">Foto Kegiatan (opsional, max 1MB, JPG/PNG)</label>
                <input
                    type="file"
                    class="form-control w-md-400px"
                    accept="image/jpeg,image/png"
                    @change="onFotoChange"
                />
                <div v-if="fotoPreview" class="mt-3">
                    <img :src="fotoPreview" class="rounded" style="max-width: 220px; max-height: 220px" />
                </div>
            </div>

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
import axios from '@/libs/axios';

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
        const foto = ref<File | null>(null);
        const fotoPreview = ref('');
        const loading = ref(false);
        const successMessage = ref('');
        const errorMessage = ref('');

        const addActivity = () => {
            activities.value.push({ jam_mulai: '', jam_selesai: '', kegiatan: '' });
        };

        const removeActivity = (index: number) => {
            activities.value.splice(index, 1);
        };

        const onFotoChange = (e: Event) => {
            const file = (e.target as HTMLInputElement).files?.[0] ?? null;
            foto.value = file;
            fotoPreview.value = file ? URL.createObjectURL(file) : '';
        };

        const submit = async () => {
            errorMessage.value = '';
            successMessage.value = '';
            loading.value = true;

            try {
                const formData = new FormData();
                formData.append('date', date.value);
                activities.value.forEach((act, i) => {
                    formData.append(`activities[${i}][jam_mulai]`, act.jam_mulai);
                    formData.append(`activities[${i}][jam_selesai]`, act.jam_selesai);
                    formData.append(`activities[${i}][kegiatan]`, act.kegiatan);
                });
                if (foto.value) {
                    formData.append('foto', foto.value);
                }

                await axios.post('/journals', formData);

                successMessage.value = 'Jurnal berhasil dikirim, menunggu approval atasan.';
                activities.value = [{ jam_mulai: '', jam_selesai: '', kegiatan: '' }];
                date.value = today;
                foto.value = null;
                fotoPreview.value = '';
            } catch (e: any) {
                errorMessage.value = e?.response?.data?.message ?? 'Gagal mengirim jurnal.';
            } finally {
                loading.value = false;
            }
        };

        return {
            date,
            activities,
            foto,
            fotoPreview,
            loading,
            successMessage,
            errorMessage,
            addActivity,
            removeActivity,
            onFotoChange,
            submit,
        };
    },
});
</script>
