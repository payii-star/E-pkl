<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title d-flex flex-column">
                <h2 class="mb-1">Jurnal Harian</h2>
                <div class="text-gray-500 fs-7">Catat kegiatanmu hari ini untuk dikirim ke pembimbing.</div>
            </div>
        </div>
        <div class="card-body pt-0">
            <!--begin::Alerts-->
            <div v-if="successMessage" class="alert alert-success d-flex align-items-center gap-2 mb-6">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="m5 13 4 4 10-10" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>{{ successMessage }}</span>
            </div>
            <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center gap-2 mb-6">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M12 8v5M12 16h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                <span>{{ errorMessage }}</span>
            </div>
            <!--end::Alerts-->

            <!--begin::Tanggal-->
            <div class="mb-8">
                <label class="form-label fw-bold">Tanggal</label>
                <input type="date" class="form-control form-control-solid w-md-300px" v-model="date" />
            </div>
            <!--end::Tanggal-->

            <!--begin::Aktivitas-->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <label class="form-label fw-bold mb-0">Aktivitas Hari Ini</label>
            </div>

            <div class="journal-activity-list mb-4">
                <div
                    v-for="(activity, index) in activities"
                    :key="index"
                    class="journal-activity-card"
                >
                    <div class="journal-activity-card__body">
                        <div class="row g-4">
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-8 text-gray-500 mb-1">Jam Mulai</label>
                                <input type="time" class="form-control form-control-solid" v-model="activity.jam_mulai" />
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-8 text-gray-500 mb-1">Jam Selesai</label>
                                <input type="time" class="form-control form-control-solid" v-model="activity.jam_selesai" />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fs-8 text-gray-500 mb-1">Kegiatan</label>
                                <textarea
                                    class="form-control form-control-solid"
                                    rows="2"
                                    v-model="activity.kegiatan"
                                    placeholder="Deskripsi kegiatan..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="journal-activity-card__remove"
                        @click="removeActivity(index)"
                        :disabled="activities.length === 1"
                        title="Hapus aktivitas ini"
                    >
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="button" class="btn btn-light-primary d-inline-flex align-items-center gap-2 mb-8" @click="addActivity">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                </svg>
                Tambah Aktivitas
            </button>
            <!--end::Aktivitas-->

            <!--begin::Foto kegiatan-->
            <div class="mb-8">
                <label class="form-label fw-bold">Foto Kegiatan <span class="text-gray-500 fw-normal">(opsional)</span></label>

                <label class="journal-dropzone">
                    <input
                        type="file"
                        class="d-none"
                        accept=".jpg,.jpeg,.png,.pdf"
                        @change="onFileChange"
                    />
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 16V4M12 4l-4 4M12 4l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    <span class="journal-dropzone__text">
                        <strong>Pilih file</strong> — JPG, PNG, atau PDF, maksimal 1MB
                    </span>
                </label>

                <div v-if="previewUrl" class="journal-photo-preview">
                    <img :src="previewUrl" alt="Preview foto kegiatan" />
                    <button type="button" class="btn btn-sm btn-light-danger" @click="removeFile">
                        Hapus foto
                    </button>
                </div>
                <div v-else-if="fotoFile" class="d-flex align-items-center gap-3 mt-3 text-gray-600 fs-7">
                    <span>File dipilih: {{ fotoFile.name }}</span>
                    <button type="button" class="btn btn-sm btn-light-danger" @click="removeFile">
                        Hapus
                    </button>
                </div>
            </div>
            <!--end::Foto kegiatan-->

            <div class="separator mb-6"></div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-6" :disabled="loading" @click="submit">
                    <span v-if="loading" class="spinner-border spinner-border-sm"></span>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ loading ? 'Mengirim...' : 'Kirim Jurnal' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent, ref } from 'vue';
import { useRouter } from 'vue-router';
import ApiService from '@/core/services/ApiService';

interface Activity {
    jam_mulai: string;
    jam_selesai: string;
    kegiatan: string;
}

export default defineComponent({
    setup() {
        const today = new Date().toISOString().slice(0, 10);
        const router = useRouter();
        const date = ref(today);
        const activities = ref<Activity[]>([{ jam_mulai: '', jam_selesai: '', kegiatan: '' }]);
        const loading = ref(false);
        const successMessage = ref('');
        const errorMessage = ref('');

        const fotoFile = ref<File | null>(null);
        const previewUrl = ref<string | null>(null);

        const addActivity = () => {
            activities.value.push({ jam_mulai: '', jam_selesai: '', kegiatan: '' });
        };

        const removeActivity = (index: number) => {
            activities.value.splice(index, 1);
        };

        const onFileChange = (ev: Event) => {
            const target = ev.target as HTMLInputElement;
            const file = target.files?.[0] ?? null;
            fotoFile.value = file;

            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
                previewUrl.value = null;
            }

            if (file && file.type.startsWith('image/')) {
                previewUrl.value = URL.createObjectURL(file);
            }
        };

        const removeFile = () => {
            fotoFile.value = null;
            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
            }
            previewUrl.value = null;
        };

        const submit = async () => {
            errorMessage.value = '';
            successMessage.value = '';
            loading.value = true;

            try {
                const payload = new FormData();
                payload.append('date', date.value);

                activities.value.forEach((activity, index) => {
                    payload.append(`activities[${index}][jam_mulai]`, activity.jam_mulai);
                    payload.append(`activities[${index}][jam_selesai]`, activity.jam_selesai);
                    payload.append(`activities[${index}][kegiatan]`, activity.kegiatan);
                });

                if (fotoFile.value) {
                    payload.append('foto', fotoFile.value);
                }

                await ApiService.post('/journals', payload);

                successMessage.value = 'Jurnal berhasil dikirim, menunggu approval atasan.';
                activities.value = [{ jam_mulai: '', jam_selesai: '', kegiatan: '' }];
                date.value = today;
                removeFile();

                router.push({ name: 'journal-history' });
            } catch (e: any) {
                errorMessage.value = e?.response?.data?.message ?? 'Gagal mengirim jurnal.';
            } finally {
                loading.value = false;
            }
        };

        return {
            date,
            activities,
            loading,
            successMessage,
            errorMessage,
            fotoFile,
            previewUrl,
            addActivity,
            removeActivity,
            onFileChange,
            removeFile,
            submit,
        };
    },
});
</script>

<style scoped>
.journal-activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.journal-activity-card {
    position: relative;
    padding: 20px 60px 20px 20px;
    border: 1px solid var(--bs-border-color, #2b2b40);
    border-radius: 10px;
    background: var(--bs-body-bg, transparent);
}

.journal-activity-card__body {
    flex: 1;
    min-width: 0;
}

.journal-activity-card__remove {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    background: var(--bs-danger-light, rgba(241, 65, 108, 0.12));
    color: var(--bs-danger, #f1416c);
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}
.journal-activity-card__remove:hover:not(:disabled) {
    background: var(--bs-danger, #f1416c);
    color: #fff;
}
.journal-activity-card__remove:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.journal-dropzone {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 18px;
    border: 1.5px dashed var(--bs-border-color, #2b2b40);
    border-radius: 10px;
    cursor: pointer;
    color: var(--bs-gray-500, #99a1b7);
    transition: border-color 0.15s ease, background 0.15s ease;
}
.journal-dropzone:hover {
    border-color: var(--bs-primary, #009ef7);
    background: var(--bs-primary-light, rgba(0, 158, 247, 0.06));
}
.journal-dropzone__text {
    font-size: 13px;
}
.journal-dropzone__text strong {
    color: var(--bs-body-color, inherit);
}

.journal-photo-preview {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 14px;
}
.journal-photo-preview img {
    width: 96px;
    height: 96px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid var(--bs-border-color, #2b2b40);
}
</style>