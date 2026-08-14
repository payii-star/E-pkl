<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { toast } from "vue3-toastify";
import Swal from "sweetalert2";
import axios from "@/libs/axios";
import Form from "./Form.vue";
import type { Project } from "@/types";

const projects = ref<Project[]>([]);
const loading = ref(false);
const selected = ref<number | null>(null);
const openForm = ref<boolean>(false);

function fetchProjects() {
    loading.value = true;
    axios
        .get("/master/projects")
        .then(({ data }) => {
            projects.value = data.data;
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? "Gagal memuat data project");
        })
        .finally(() => {
            loading.value = false;
        });
}

function edit(project: Project) {
    selected.value = project.id as number;
    openForm.value = true;
}

function remove(project: Project) {
    Swal.fire({
        title: "Apakah anda yakin?",
        text: `Hapus project "${project.title}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batalkan",
        reverseButtons: true,
        customClass: {
            confirmButton: "btn btn-danger btn-sm",
            cancelButton: "btn btn-secondary btn-sm",
        },
        buttonsStyling: false,
    }).then((result) => {
        if (!result.isConfirmed) return;
        axios
            .delete(`/master/projects/${project.id}`)
            .then(() => {
                toast.success("Project berhasil dihapus");
                fetchProjects();
            })
            .catch((err: any) => {
                toast.error(err.response?.data?.message ?? "Gagal menghapus project");
            });
    });
}

const refresh = () => fetchProjects();

watch(openForm, (val) => {
    if (!val) selected.value = null;
    window.scrollTo(0, 0);
});

onMounted(fetchProjects);
</script>

<template>
    <Form
        :selected="selected"
        @close="openForm = false"
        v-if="openForm"
        @refresh="refresh"
    />

    <div class="card">
        <div class="card-header align-items-center">
            <h2 class="mb-0">List Projects</h2>
            <button
                type="button"
                class="btn btn-sm btn-primary ms-auto"
                v-if="!openForm"
                @click="openForm = true"
            >
                Tambah
                <i class="la la-plus"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-rounded table-hover table-striped border gy-7 gs-7"
                >
                    <thead class="bg-gray-200">
                        <tr
                            class="fw-bolder fs-6 text-gray-800 border-bottom border-gray-200"
                        >
                            <th class="py-4">#</th>
                            <th class="py-4">Thumbnail</th>
                            <th class="py-4">Title</th>
                            <th class="py-4">Kategori</th>
                            <th class="py-4">Featured</th>
                            <th class="py-4">Urutan</th>
                            <th class="py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4">Memuat data...</td>
                        </tr>
                        <template v-else-if="projects.length">
                            <tr
                                v-for="(project, index) in projects"
                                :key="project.id"
                            >
                                <td class="py-4">{{ index + 1 }}</td>
                                <td class="py-4">
                                    <img
                                        :src="project.image ? `/storage/${project.image}` : ''"
                                        :alt="project.title"
                                        class="rounded"
                                        style="
                                            width: 60px;
                                            height: 60px;
                                            object-fit: cover;
                                        "
                                        onerror="this.src='https://placehold.co/60x60?text=No+Image'"
                                    />
                                </td>
                                <td class="py-4">{{ project.title }}</td>
                                <td class="py-4">
                                    <span
                                        class="badge"
                                        :class="
                                            project.category === 'mobile'
                                                ? 'badge-light-primary'
                                                : 'badge-light-info'
                                        "
                                    >
                                        {{
                                            project.category === 'mobile'
                                                ? "Mobile"
                                                : "Web"
                                        }}
                                    </span>
                                </td>
                                <td class="py-4">
                                    <span
                                        class="badge"
                                        :class="
                                            project.is_featured
                                                ? 'badge-light-success'
                                                : 'badge-light-secondary'
                                        "
                                    >
                                        {{
                                            project.is_featured
                                                ? "Featured"
                                                : "Biasa"
                                        }}
                                    </span>
                                </td>
                                <td class="py-4">{{ project.urutan }}</td>
                                <td class="py-4">
                                    <div class="d-flex gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-icon btn-info"
                                            @click="edit(project)"
                                        >
                                            <i class="la la-pencil fs-2"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-icon btn-danger"
                                            @click="remove(project)"
                                        >
                                            <i class="la la-trash fs-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="7" class="text-center py-4">
                                Data tidak ditemukan
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
