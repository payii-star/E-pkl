<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref } from "vue";
import { toast } from "vue3-toastify";
import type { ContactMessage } from "@/types";
import ApiService from "@/core/services/ApiService";

const props = defineProps({
    selected: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close"]);

const message = ref<ContactMessage>({} as ContactMessage);

function getDetail() {
    block(document.getElementById("detail-contact-message"));
    // TODO: cocokkan endpoint ini dengan API Destria
    ApiService.get("master/contact-messages", props.selected)
        .then(({ data }) => {
            message.value = data.contact_message;
        })
        .catch((err: any) => {
            toast.error(err.response.data.message);
        })
        .finally(() => {
            unblock(document.getElementById("detail-contact-message"));
        });
}

onMounted(() => {
    if (props.selected) {
        getDetail();
    }
});
</script>

<template>
    <div class="card mb-10" id="detail-contact-message">
        <div class="card-header align-items-center">
            <h2 class="mb-0">Detail Pesan Masuk</h2>
            <button
                type="button"
                class="btn btn-sm btn-light-danger ms-auto"
                @click="emit('close')"
            >
                Tutup
                <i class="la la-times-circle p-0"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-5">
                    <label class="form-label fw-bold fs-6">Nama</label>
                    <div class="fs-5">{{ message.name }}</div>
                </div>
                <div class="col-md-6 mb-5">
                    <label class="form-label fw-bold fs-6">Email</label>
                    <div class="fs-5">{{ message.email }}</div>
                </div>
                <div class="col-md-6 mb-5">
                    <label class="form-label fw-bold fs-6">No. Telp</label>
                    <div class="fs-5">{{ message.phone || "-" }}</div>
                </div>
                <div class="col-md-6 mb-5">
                    <label class="form-label fw-bold fs-6">
                        Tanggal Masuk
                    </label>
                    <div class="fs-5">{{ message.created_at }}</div>
                </div>
                <div class="col-md-12 mb-5">
                    <label class="form-label fw-bold fs-6">Subjek</label>
                    <div class="fs-5">{{ message.subject || "-" }}</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold fs-6">Isi Pesan</label>
                    <div class="fs-5" style="white-space: pre-line">
                        {{ message.message }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>