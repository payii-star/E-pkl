<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>Riwayat Jurnal</h2></div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                        <tr>
                            <th class="text-nowrap">No</th>
                            <th class="text-nowrap">Tanggal</th>
                            <th class="text-nowrap">Breakdown Aktivitas</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-nowrap">Catatan Pembimbing</th>
                            <th class="text-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!journals.length">
                            <td colspan="6" class="text-center text-gray-500">Belum ada jurnal</td>
                        </tr>
                        <tr v-for="(journal, i) in journals" :key="journal.id">
                            <td>{{ i + 1 }}</td>
                            <td class="text-nowrap">{{ formatDate(journal.date) }}</td>
                            <td style="min-width: 220px">
                                <div v-for="act in journal.activities" :key="act.id" class="mb-2">
                                    <span class="fw-bold text-nowrap">{{ act.jam_mulai }} - {{ act.jam_selesai }}</span>
                                    <div class="text-gray-700">{{ truncate(act.kegiatan) }}</div>
                                </div>
                            </td>
                            <td class="text-nowrap">
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
                                <div v-if="journal.last_edited_at" class="text-gray-500 fs-9 fst-italic mt-1">
                                    Diedit pada {{ formatDate(journal.last_edited_at) }}
                                </div>
                            </td>
                            <td style="min-width: 180px">
                                <span v-if="journal.catatan_approval" :class="journal.status === 'rejected' ? 'text-danger' : 'text-gray-700'">
                                    {{ truncate(journal.catatan_approval) }}
                                </span>
                                <span v-else class="text-gray-500 fst-italic">
                                    {{ journal.status === 'pending' ? 'Menunggu diperiksa' : 'Tidak ada catatan' }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-light-primary" @click="openDetail(journal)">Detail</button>
                                    <button class="btn btn-sm btn-light-warning" @click="openEdit(journal)">Edit</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

        <!--begin::Modal Edit-->
        <div v-if="editing" class="modal-backdrop-custom" @click.self="closeEdit">
            <div class="modal-box-custom">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Edit Jurnal — {{ formatDate(editing.date) }}</h3>
                    <button class="btn btn-sm btn-icon btn-light" @click="closeEdit">✕</button>
                </div>

                <div v-if="editing.status === 'approved' || editing.status === 'rejected'" class="alert alert-warning fs-7 mb-4">
                    Jurnal ini sebelumnya sudah
                    <strong>{{ editing.status === 'approved' ? 'Disetujui' : 'Ditolak' }}</strong>.
                    Kalau kamu simpan perubahan, status akan kembali menjadi
                    <strong>Menunggu Approval</strong> dan perlu diperiksa ulang oleh pembimbing.
                </div>

                <div v-if="editError" class="alert alert-danger fs-7 mb-4">{{ editError }}</div>

                <div class="edit-activity-list mb-4">
                    <div v-for="(act, idx) in editActivities" :key="idx" class="mb-3 p-3 border rounded position-relative">
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-8 text-gray-500 mb-1">Jam Mulai</label>
                                <input type="time" class="form-control form-control-solid" v-model="act.jam_mulai" />
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fs-8 text-gray-500 mb-1">Jam Selesai</label>
                                <input type="time" class="form-control form-control-solid" v-model="act.jam_selesai" />
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fs-8 text-gray-500 mb-1">Kegiatan</label>
                                <textarea class="form-control form-control-solid" rows="2" v-model="act.kegiatan"></textarea>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-icon btn-light-danger position-absolute top-0 end-0 m-2"
                            :disabled="editActivities.length === 1"
                            @click="removeEditActivity(idx)"
                            title="Hapus aktivitas ini"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-light-primary mb-6" @click="addEditActivity">
                    + Tambah Aktivitas
                </button>

                <!--begin::Foto kegiatan (edit)-->
                <div class="mb-6">
                    <label class="form-label fw-bold">Foto Kegiatan <span class="text-gray-500 fw-normal">(opsional)</span></label>

                    <div v-if="!editFotoFile" class="d-flex align-items-center gap-3 mb-3">
                        <img
                            v-if="editing.foto"
                            :src="fotoUrl(editing.foto)"
                            alt="Foto kegiatan saat ini"
                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px"
                        />
                        <span v-else class="text-gray-500 fst-italic fs-7">Belum ada foto sebelumnya.</span>
                    </div>

                    <div v-if="editFotoPreview" class="d-flex align-items-center gap-3 mb-3">
                        <img
                            :src="editFotoPreview"
                            alt="Preview foto baru"
                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px"
                        />
                        <span class="text-gray-600 fs-7">Foto baru dipilih: {{ editFotoFile?.name }}</span>
                        <button type="button" class="btn btn-sm btn-light-danger" @click="removeEditFoto">Batal ganti</button>
                    </div>

                    <label class="journal-dropzone">
                        <input
                            type="file"
                            class="d-none"
                            accept=".jpg,.jpeg,.png"
                            @change="onEditFileChange"
                        />
                        <span class="journal-dropzone__text">
                            <strong>{{ editing.foto ? 'Ganti foto' : 'Pilih file' }}</strong> — JPG atau PNG, maksimal 1MB.
                            Kalau tidak diganti, foto lama tetap dipakai.
                        </span>
                    </label>
                </div>
                <!--end::Foto kegiatan (edit)-->

                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-light" @click="closeEdit" :disabled="editLoading">Batal</button>
                    <button class="btn btn-primary" @click="submitEdit" :disabled="editLoading">
                        <span v-if="editLoading" class="spinner-border spinner-border-sm me-2"></span>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
        <!--end::Modal Edit-->
    </div>
</template>

<script lang="ts">
import { defineComponent, onMounted, ref } from 'vue';
import ApiService from '@/core/services/ApiService';

export default defineComponent({
    setup() {
        const journals = ref<any[]>([]);
        const selected = ref<any>(null);

        // Backend kadang mengirim jam dalam format "HH:mm:ss" (kolom TIME),
        // tapi <input type="time"> & validasi backend butuh "HH:mm" persis.
        // Fungsi ini memastikan formatnya selalu konsisten dipotong ke HH:mm.
        const toHHMM = (value: string): string => {
            if (!value) return '';
            return String(value).slice(0, 5);
        };

        // State untuk modal Edit
        const editing = ref<any>(null);
        const editActivities = ref<any[]>([]);
        const editLoading = ref(false);
        const editError = ref('');
        const editFotoFile = ref<File | null>(null);
        const editFotoPreview = ref<string | null>(null);

        const onEditFileChange = (ev: Event) => {
            const target = ev.target as HTMLInputElement;
            const file = target.files?.[0] ?? null;
            editFotoFile.value = file;

            if (editFotoPreview.value) {
                URL.revokeObjectURL(editFotoPreview.value);
                editFotoPreview.value = null;
            }

            if (file && file.type.startsWith('image/')) {
                editFotoPreview.value = URL.createObjectURL(file);
            }
        };

        const removeEditFoto = () => {
            if (editFotoPreview.value) {
                URL.revokeObjectURL(editFotoPreview.value);
            }
            editFotoFile.value = null;
            editFotoPreview.value = null;
        };

        const openEdit = (journal: any) => {
            editing.value = journal;
            editError.value = '';
            removeEditFoto();
            editActivities.value = (journal.activities ?? []).map((a: any) => ({
                jam_mulai: toHHMM(a.jam_mulai),
                jam_selesai: toHHMM(a.jam_selesai),
                kegiatan: a.kegiatan,
            }));
            if (editActivities.value.length === 0) {
                editActivities.value.push({ jam_mulai: '', jam_selesai: '', kegiatan: '' });
            }
        };

        const closeEdit = () => {
            editing.value = null;
            editActivities.value = [];
            editError.value = '';
            removeEditFoto();
        };

        const addEditActivity = () => {
            editActivities.value.push({ jam_mulai: '', jam_selesai: '', kegiatan: '' });
        };

        const removeEditActivity = (idx: number) => {
            editActivities.value.splice(idx, 1);
        };

        const submitEdit = async () => {
            if (!editing.value) return;

            editError.value = '';
            editLoading.value = true;

            try {
                const payload = new FormData();
                payload.append('_method', 'PUT');

                editActivities.value.forEach((act, index) => {
                    payload.append(`activities[${index}][jam_mulai]`, toHHMM(act.jam_mulai));
                    payload.append(`activities[${index}][jam_selesai]`, toHHMM(act.jam_selesai));
                    payload.append(`activities[${index}][kegiatan]`, act.kegiatan);
                });

                if (editFotoFile.value) {
                    payload.append('foto', editFotoFile.value);
                }

                // Laravel tidak membaca body multipart pada method PUT asli,
                // jadi dikirim sebagai POST dengan _method override di atas.
                await ApiService.post(`/journals/${editing.value.id}`, payload);

                closeEdit();
                await fetchHistory();
            } catch (e: any) {
                editError.value = e?.response?.data?.message ?? 'Gagal menyimpan perubahan jurnal.';
            } finally {
                editLoading.value = false;
            }
        };

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

        return {
            journals,
            selected,
            formatDate,
            statusLabel,
            truncate,
            fotoUrl,
            openDetail,
            editing,
            editActivities,
            editLoading,
            editError,
            editFotoFile,
            editFotoPreview,
            onEditFileChange,
            removeEditFoto,
            openEdit,
            closeEdit,
            addEditActivity,
            removeEditActivity,
            submitEdit,
        };
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