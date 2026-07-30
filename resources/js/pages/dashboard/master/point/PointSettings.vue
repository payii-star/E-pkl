<template>
    <div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title">Manajemen Aturan Poin</h3>
    </div>
    <div class="card-body">
        <form @submit.prevent="saveSettings">
        <div class="mb-8">
            <h4 class="mb-4">Aturan Perolehan Poin</h4>
            <label class="form-label">Setiap belanja senilai...</label>
            <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control" v-model="formattedRupiahPerPoint" />
            </div>
            <div class="form-text">
            ...akan mendapatkan 1 Poin.
            </div>
        </div>

        <div class="separator separator-dashed"></div>

        <div class="mt-8">
            <h4 class="mb-4">Aturan Penukaran Poin</h4>
            <label class="form-label">Nilai 1 Poin setara dengan...</label>
            <div class="input-group" style="max-width: 300px;">
            <span class="input-group-text">Rp</span>
            <input type="text" class="form-control" v-model="formattedPointRedemptionValue" />
            </div>
            <div class="form-text">
            Nilai ini akan digunakan sebagai diskon saat member menukar poin.
            </div>
        </div>
        
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                <span v-if="isLoading" class="spinner-border spinner-border-sm"></span>
                <span v-else>Simpan Pengaturan</span>
            </button>
        </div>
        </form>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import ApiService from '@/core/services/ApiService';
import Swal from 'sweetalert2';

// Helper format
const formatRupiah = (val: number) => new Intl.NumberFormat("id-ID").format(val);
const parseRupiah = (val: string) => parseInt(String(val).replace(/[^0-9]/g, "")) || 0;

const settings = ref({
    rupiah_per_point: 10000,
    point_redemption_value: 100,
});
const isLoading = ref(false);

const formattedRupiahPerPoint = computed({
    get: () => formatRupiah(settings.value.rupiah_per_point),
    set: (newValue) => {
    settings.value.rupiah_per_point = parseRupiah(newValue);
    },
});

const formattedPointRedemptionValue = computed({
    get: () => formatRupiah(settings.value.point_redemption_value),
    set: (newValue) => {
    settings.value.point_redemption_value = parseRupiah(newValue);
    },
});

const fetchSettings = async () => {
    isLoading.value = true;
    try {
    // DIUBAH: Path URL diperbarui
    const { data } = await ApiService.get('/master/points/settings');
    settings.value.rupiah_per_point = parseInt(data.rupiah_per_point);
    settings.value.point_redemption_value = parseInt(data.point_redemption_value);
    } catch (error) {
    console.error("Gagal mengambil pengaturan poin:", error);
    } finally {
    isLoading.value = false;
    }
};

const saveSettings = async () => {
    isLoading.value = true;
    try {
    // DIUBAH: Path URL diperbarui
    await ApiService.post('/master/points/settings', settings.value);
    Swal.fire("Berhasil!", "Pengaturan poin telah diperbarui.", "success");
    } catch (error) {
    Swal.fire("Error!", "Gagal menyimpan pengaturan.", "error");
    } finally {
    isLoading.value = false;
    }
};

onMounted(() => {
    fetchSettings();
});
</script>