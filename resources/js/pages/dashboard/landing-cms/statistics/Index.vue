<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { toast } from "vue3-toastify";
import Swal from "sweetalert2";
import axios from "@/libs/axios";
import Form from "./Form.vue";
import type { Statistic } from "@/types";

const statistics = ref<Statistic[]>([]);
const loading = ref(false);

const selected = ref<Statistic | null>(null);
const openForm = ref(false);

function fetchStatistics() {
    loading.value = true;

    axios
        .get("/master/statistics")
        .then(({ data }) => {
            statistics.value = data.data ?? [];
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ??
                    "Gagal memuat data statistik"
            );
        })
        .finally(() => {
            loading.value = false;
        });
}

function onAdd() {
    selected.value = null;
    openForm.value = true;
}

function onEdit(statistic: Statistic) {
    selected.value = statistic;
    openForm.value = true;
}

function onDelete(statistic: Statistic) {
    Swal.fire({
        title: "Apakah anda yakin?",
        text: `Hapus statistik "${statistic.label}"?`,
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
        if (!result.isConfirmed) {
            return;
        }

        axios
            .delete(`/master/statistics/${statistic.id}`)
            .then(() => {
                toast.success(
                    "Statistik berhasil dihapus"
                );

                fetchStatistics();
            })
            .catch((err: any) => {
                toast.error(
                    err.response?.data?.message ??
                        "Gagal menghapus statistik"
                );
            });
    });
}

function refresh() {
    fetchStatistics();
}

function closeForm() {
    openForm.value = false;
}

watch(openForm, (value) => {
    if (!value) {
        selected.value = null;
    }

    window.scrollTo(0, 0);
});

onMounted(() => {
    fetchStatistics();
});
</script>

<template>
    <!-- FORM TAMBAH / EDIT -->
    <Form
        v-if="openForm"
        :selected="selected"
        @close="closeForm"
        @refresh="refresh"
    />

    <!-- LIST STATISTICS -->
    <div class="card">
        <div class="card-header align-items-center">
            <h2 class="mb-0">
                List Statistics
            </h2>

            <button
                type="button"
                class="btn btn-sm btn-primary ms-auto"
                v-if="!openForm"
                @click="onAdd"
            >
                Tambah

                <i class="la la-plus"></i>
            </button>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table
                    class="table table-rounded table-hover table-striped border gy-7 gs-7"
                    id="table-statistics"
                >
                    <thead class="bg-gray-200">
                        <tr
                            class="fw-bolder fs-6 text-gray-800 border-bottom border-gray-200"
                        >
                            <th class="py-4">
                                #
                            </th>

                            <th class="py-4">
                                Icon
                            </th>

                            <th class="py-4">
                                Nilai
                            </th>

                            <th class="py-4">
                                Label
                            </th>

                            <th class="py-4">
                                Urutan
                            </th>

                            <th class="py-4">
                                Status
                            </th>

                            <th class="py-4">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- LOADING -->
                        <tr v-if="loading">
                            <td
                                colspan="7"
                                class="text-center py-4"
                            >
                                Memuat data...
                            </td>
                        </tr>

                        <!-- DATA -->
                        <template
                            v-else-if="statistics.length"
                        >
                            <tr
                                v-for="(
                                    statistic, index
                                ) in statistics"
                                :key="statistic.id"
                            >
                                <!-- NO -->
                                <td class="py-4">
                                    {{ index + 1 }}
                                </td>

                                <!-- ICON -->
                                <td class="py-4">
                                    <i
                                        v-if="statistic.icon"
                                        :class="[
                                            'fa-solid',
                                            `fa-${statistic.icon}`,
                                        ]"
                                        class="fs-2"
                                    ></i>

                                    <span
                                        v-else
                                        class="text-muted"
                                    >
                                        -
                                    </span>
                                </td>

                                <!-- NILAI -->
                                <td class="py-4">
                                    {{ statistic.statistic }}
                                </td>

                                <!-- LABEL -->
                                <td class="py-4">
                                    {{ statistic.label }}
                                </td>

                                <!-- URUTAN -->
                                <td class="py-4">
                                    {{ statistic.urutan }}
                                </td>

                                <!-- STATUS -->
                                <td class="py-4">
                                    <span
                                        class="badge"
                                        :class="
                                            statistic.is_active
                                                ? 'badge-light-success'
                                                : 'badge-light-danger'
                                        "
                                    >
                                        {{
                                            statistic.is_active
                                                ? "Aktif"
                                                : "Nonaktif"
                                        }}
                                    </span>
                                </td>

                                <!-- AKSI -->
                                <td class="py-4">
                                    <div
                                        class="d-flex gap-2"
                                    >
                                        <!-- EDIT -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-icon btn-info"
                                            @click="
                                                onEdit(
                                                    statistic
                                                )
                                            "
                                        >
                                            <i
                                                class="la la-pencil fs-2"
                                            ></i>
                                        </button>

                                        <!-- DELETE -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-icon btn-danger"
                                            @click="
                                                onDelete(
                                                    statistic
                                                )
                                            "
                                        >
                                            <i
                                                class="la la-trash fs-2"
                                            ></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- EMPTY -->
                        <tr v-else>
                            <td
                                colspan="7"
                                class="text-center py-4"
                            >
                                Data tidak ditemukan
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>