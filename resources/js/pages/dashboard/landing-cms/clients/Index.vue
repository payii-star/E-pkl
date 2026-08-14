<script setup lang="ts">
import { onMounted, ref } from "vue";
import ApiService from "@/core/services/ApiService";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import { block, unblock } from "@/libs/utils";
import Form from "./Form.vue";

interface ClientLogo {
    id: string;
    url: string;
    name: string;
    short?: string;
}

const clients = ref<ClientLogo[]>([]);
const openForm = ref(false);
const selected = ref<string>("");
const dragIndex = ref<number | null>(null);

function fetchClients() {
    block(document.getElementById("client-logo-list"));
    ApiService.get("master/client-logos", "")
        .then(({ data }) => {
            clients.value = data.data;
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message || "Gagal memuat data");
        })
        .finally(() => {
            unblock(document.getElementById("client-logo-list"));
        });
}

function refresh() {
    openForm.value = false;
    selected.value = "";
    fetchClients();
}

function edit(id: string) {
    selected.value = id;
    openForm.value = true;
}

function remove(id: string) {
    if (!confirm("Yakin ingin menghapus client ini?")) return;

    block(document.getElementById("client-logo-list"));
    axios
        .delete(`/master/client-logos/${id}`)
        .then(() => {
            toast.success("Client berhasil dihapus");
            fetchClients();
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message || "Gagal menghapus data");
        })
        .finally(() => {
            unblock(document.getElementById("client-logo-list"));
        });
}

// ── Drag & drop reorder (native HTML5, tanpa library tambahan) ──
function onDragStart(index: number) {
    dragIndex.value = index;
}

function onDrop(index: number) {
    if (dragIndex.value === null || dragIndex.value === index) return;

    const moved = clients.value.splice(dragIndex.value, 1)[0];
    clients.value.splice(index, 0, moved);
    dragIndex.value = null;

    saveOrder();
}

function saveOrder() {
    axios
        .post("/master/client-logos/reorder", {
            ids: clients.value.map((c) => c.id),
        })
        .then(() => {
            toast.success("Urutan berhasil disimpan");
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message || "Gagal menyimpan urutan");
            fetchClients(); // rollback tampilan kalau gagal disimpan
        });
}

onMounted(fetchClients);
</script>

<template>
    <Form
        :selected="selected"
        @close="openForm = false"
        v-if="openForm"
        @refresh="refresh"
    />

    <div class="card" id="client-logo-list">
        <div class="card-header align-items-center">
            <h2 class="mb-0">List Client / Mitra</h2>
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
            <p class="text-muted fs-7 mb-5">
                Geser (drag) tiap baris untuk mengubah urutan tampil di halaman depan.
            </p>

            <div
                v-for="(client, index) in clients"
                :key="client.id"
                class="d-flex align-items-center border rounded p-3 mb-3"
                draggable="true"
                @dragstart="onDragStart(index)"
                @dragover.prevent
                @drop="onDrop(index)"
                style="cursor: grab;"
            >
                <i class="la la-bars fs-2 text-muted me-3"></i>
                <img
                    :src="client.url"
                    :alt="client.name"
                    style="width: 56px; height: 56px; object-fit: contain;"
                    class="me-4"
                />
                <div class="flex-grow-1">
                    <div class="fw-bold">{{ client.name }}</div>
                    <div class="text-muted fs-7" v-if="client.short">{{ client.short }}</div>
                </div>
                <div class="d-flex gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-icon btn-info"
                        @click="edit(client.id)"
                    >
                        <i class="la la-pencil fs-2"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-icon btn-danger"
                        @click="remove(client.id)"
                    >
                        <i class="la la-trash fs-2"></i>
                    </button>
                </div>
            </div>

            <div v-if="!clients.length" class="text-center text-muted py-10">
                Belum ada data client.
            </div>
        </div>
    </div>
</template>