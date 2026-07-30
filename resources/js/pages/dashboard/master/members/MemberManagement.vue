<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title"><h2>Manajemen Member</h2></div>
            <div class="card-toolbar">
                </div>
        </div>
        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th>Nama</th>
                        <th>No. Telepon</th>
                        <th>Email</th>
                        <th>Alamat</th> <th>Poin</th>
                        <th>Bergabung Sejak</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    <tr v-for="member in members" :key="member.id">
                        <td>{{ member.name }}</td>
                        <td>{{ member.phone_number }}</td>
                        <td>{{ member.email }}</td>
                        <td>{{ member.address || '-' }}</td> <td>{{ member.points?.toLocaleString('id-ID') || 0 }}</td>
                        <td>{{ new Date(member.created_at).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) }}</td>
                        <td class="text-end">
                            <a @click="openModal(member)" href="#" class="btn btn-icon btn-sm btn-light-primary me-2" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a @click="deleteMember(member)" href="#" class="btn btn-icon btn-sm btn-light-danger" title="Hapus"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <MemberModal :member="memberToEdit" @member-saved="fetchMembers" />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import ApiService from '@/core/services/ApiService';
import MemberModal from './MemberModal.vue';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

interface Member {
    id: number;
    name: string;
    phone_number: string;
    email: string;
    address: string;
    points: number;
    created_at: string;
}

const members = ref<Member[]>([]);
const memberToEdit = ref<Member | null>(null);

const fetchMembers = () => {
    ApiService.get('/master/members').then(({ data }) => {
    members.value = data.data;
    });
};

const openModal = (member: Member) => {
    memberToEdit.value = member;
    const modalElement = document.getElementById('kt_modal_member');
    if (modalElement) {
        new Modal(modalElement).show();
    }
};

const deleteMember = (member: Member) => {
    Swal.fire({
    text: `Apakah Anda yakin ingin menghapus member "${member.name}"?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, hapus!",
    cancelButtonText: "Batal",
    customClass: {
        confirmButton: "btn btn-danger",
        cancelButton: "btn btn-primary",
    },
    }).then((result) => {
    if (result.isConfirmed) {
        ApiService.delete(`/master/members/${member.id}`)
        .then(() => {
            Swal.fire("Berhasil!", "Member telah dihapus.", "success");
            fetchMembers();
        });
    }
    });
};

onMounted(fetchMembers);
</script>