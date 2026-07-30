<template>
    <div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title"><h2>Manajemen Promo</h2></div>
        <div class="card-toolbar">
        <button @click="openModal()" class="btn btn-primary">
            Tambah Promo
        </button>
        </div>
    </div>
    <div class="card-body pt-0">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
        <thead>
            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
            <th>Nama Promo</th>
            <th>Kode</th>
            <th>Nilai</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Berakhir</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="promo in promos" :key="promo.id">
            <td>{{ promo.name }}</td>
            <td><span class="badge badge-light-success">{{ promo.code }}</span></td>
            <td>{{ promo.type === 'percentage' ? `${promo.value}%` : `Rp ${formatNumber(promo.value)}` }}</td>
            <td>{{ formatDate(promo.start_date) }}</td>
            <td>{{ formatDate(promo.end_date) }}</td>
            
            <td>
                <span :class="promo.is_active ? 'badge-light-success' : 'badge-light-danger'" class="badge">
                    {{ promo.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
                <span v-if="promo.is_member_only" class="badge badge-light-info ms-2">
                    Member Only
                </span>
            </td>
            <td class="text-end">
                <a @click="openModal(promo)" href="#" class="btn btn-icon btn-sm btn-light-primary me-2"><i class="bi bi-pencil"></i></a>
                <a @click="deletePromo(promo)" href="#" class="btn btn-icon btn-sm btn-light-danger"><i class="bi bi-trash"></i></a>
            </td>
            </tr>
        </tbody>
        </table>
    </div>
    </div>
    
    <PromoModal :promo="promoToEdit" @promo-saved="fetchPromos" />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApiService from '@/core/services/ApiService';
import PromoModal from './PromoModal.vue';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

const promos = ref<any[]>([]); // Tambahkan <any[]> untuk tipe data yang lebih fleksibel
const promoToEdit = ref(null);

const formatNumber = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('id-ID').format(parseFloat(value));
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};

const fetchPromos = () => {
    ApiService.get('/master/promos').then(({ data }) => {
        promos.value = data.data;
    });
};

const openModal = (promo = null) => {
    promoToEdit.value = promo;
    const modalElement = document.getElementById('kt_modal_promo');
    if (modalElement) {
        new Modal(modalElement).show();
    }
};

const deletePromo = (promo) => {
    Swal.fire({
        text: `Apakah Anda yakin ingin menghapus promo "${promo.name}"?`,
        icon: "warning",
        showCancelButton: true,
        buttonsStyling: false,
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
        customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-primary",
        },
    }).then((result) => {
        if (result.isConfirmed) {
        ApiService.delete(`/master/promos/${promo.id}`)
            .then(() => {
            Swal.fire("Berhasil!", "Promo telah dihapus.", "success");
            fetchPromos();
            })
            .catch(({ response }) => {
            console.error(response);
            Swal.fire("Error!", "Gagal menghapus promo.", "error");
            });
        }
    });
};

onMounted(fetchPromos);
</script>