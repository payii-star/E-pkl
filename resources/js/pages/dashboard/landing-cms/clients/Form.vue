<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import ApiService from "@/core/services/ApiService";

interface ClientLogo {
    id?: string;
    url?: string;
    name: string;
    short?: string;
}

const props = defineProps({
    selected: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const client = ref<ClientLogo>({ name: "", short: "" });
const fileTypes = ref(["image/jpeg", "image/png", "image/jpg", "image/webp"]);
const logo = ref<any>([]);
const formRef = ref();

const formSchema = Yup.object().shape({
    name: Yup.string().required("Nama instansi harus diisi"),
});

function getEdit() {
    block(document.getElementById("form-client-logo"));
    ApiService.get("master/client-logos", props.selected)
        .then(({ data }) => {
            client.value = data.client;
            logo.value = data.client.url ? [data.client.url] : [];
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message || "Gagal memuat data");
        })
        .finally(() => {
            unblock(document.getElementById("form-client-logo"));
        });
}

function submit() {
    const formData = new FormData();
    formData.append("name", client.value.name);
    formData.append("short", client.value.short || "");

    if (logo.value.length && logo.value[0].file) {
        formData.append("logo", logo.value[0].file);
    }
    if (props.selected) {
        formData.append("_method", "PUT");
    }

    block(document.getElementById("form-client-logo"));
    axios({
        method: "post",
        url: props.selected
            ? `/master/client-logos/${props.selected}`
            : "/master/client-logos/store",
        data: formData,
        headers: {
            "Content-Type": "multipart/form-data",
        },
    })
        .then(() => {
            emit("close");
            emit("refresh");
            toast.success("Data berhasil disimpan");
            formRef.value.resetForm();
        })
        .catch((err: any) => {
            formRef.value.setErrors(err.response?.data?.errors || {});
            toast.error(err.response?.data?.message || "Gagal menyimpan data");
        })
        .finally(() => {
            unblock(document.getElementById("form-client-logo"));
        });
}

onMounted(() => {
    if (props.selected) {
        getEdit();
    }
});

watch(
    () => props.selected,
    () => {
        if (props.selected) {
            getEdit();
        }
    }
);
</script>

<template>
    <VForm
        class="form card mb-10"
        @submit="submit"
        :validation-schema="formSchema"
        id="form-client-logo"
        ref="formRef"
    >
        <div class="card-header align-items-center">
            <h2 class="mb-0">
                {{ selected ? "Edit" : "Tambah" }} Client / Mitra
            </h2>
            <button
                type="button"
                class="btn btn-sm btn-light-danger ms-auto"
                @click="emit('close')"
            >
                Batal
                <i class="la la-times-circle p-0"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Nama Instansi
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="name"
                            autocomplete="off"
                            v-model="client.name"
                            placeholder="Contoh: Pemerintah Provinsi Jawa Timur"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="name" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Label Singkat (opsional)
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="short"
                            autocomplete="off"
                            v-model="client.short"
                            placeholder="Contoh: Pemprov Jatim"
                        />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                            :class="{ required: !selected }"
                        >
                            Logo
                        </label>
                        <file-upload
                            :files="logo"
                            :accepted-file-types="fileTypes"
                            v-on:updatefiles="(file) => (logo = file)"
                        ></file-upload>
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="logo" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex">
            <button type="submit" class="btn btn-primary btn-sm ms-auto">
                Simpan
            </button>
        </div>
    </VForm>
</template>