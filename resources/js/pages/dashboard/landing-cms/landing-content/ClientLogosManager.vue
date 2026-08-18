<template>
    <div class="card mt-6">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold">Client Logos (Section "Our Clients")</h3>
            </div>
            <div class="card-toolbar">
                <button class="btn btn-sm btn-light-primary" @click="addRow">
                    <i class="la la-plus"></i> Tambah Client
                </button>
            </div>
        </div>
        <div class="card-body pt-2">
            <div v-if="loading" class="text-center py-8">
                <span class="spinner-border spinner-border-sm"></span>
            </div>

            <div v-else-if="logos.length === 0" class="text-muted text-center py-6">
                Belum ada client. Klik "Tambah Client" buat mulai.
            </div>

            <div v-else class="d-flex flex-column gap-4">
                <div
                    v-for="(logo, i) in logos"
                    :key="i"
                    class="d-flex align-items-start gap-3 border rounded p-4"
                >
                    <div class="flex-fill">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fs-7 fw-semibold">Nama Lengkap</label>
                                <input
                                    v-model="logo.name"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Pemerintah Kabupaten Blitar"
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-7 fw-semibold">Nama Singkat</label>
                                <input
                                    v-model="logo.short"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Pemkab Blitar"
                                />
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fs-7 fw-semibold">
                                    URL Logo (opsional, kosongkan kalau belum ada)
                                </label>
                                <input
                                    v-model="logo.url"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="https://.../logo.png"
                                />
                            </div>
                        </div>
                    </div>
                    <button
                        class="btn btn-icon btn-sm btn-light-danger mt-6"
                        @click="removeRow(i)"
                        title="Hapus"
                    >
                        <i class="la la-trash"></i>
                    </button>
                </div>
            </div>

            <div class="mt-6">
                <button class="btn btn-primary" :disabled="saving" @click="save">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    Simpan Client Logos
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import { block, unblock } from "@/libs/utils";

interface ClientLogoRow {
    name: string;
    short: string;
    url: string;
}

const logos = ref<ClientLogoRow[]>([]);
const loading = ref(false);
const saving = ref(false);

async function fetchLogos() {
    loading.value = true;
    try {
        const res = await axios.get("/master/landing-content/client-logos");
        logos.value = res.data?.data ?? [];
    } catch (e) {
        console.error("Gagal memuat client logos:", e);
    } finally {
        loading.value = false;
    }
}

function addRow() {
    logos.value.push({ name: "", short: "", url: "" });
}

function removeRow(i: number) {
    logos.value.splice(i, 1);
}

async function save() {
    // Buang baris yang nama-nya kosong biar nggak nyimpen sampah
    const cleaned = logos.value.filter((l) => l.name.trim() !== "");

    saving.value = true;
    block("body");
    try {
        await axios.post("/master/landing-content/client-logos", { logos: cleaned });
        toast.success("Client logos berhasil disimpan");
        logos.value = cleaned;
    } catch (e: any) {
        toast.error(e.response?.data?.message ?? "Gagal menyimpan client logos");
    } finally {
        saving.value = false;
        unblock("body");
    }
}

onMounted(fetchLogos);
</script>