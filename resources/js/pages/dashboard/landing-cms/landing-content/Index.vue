<script setup lang="ts">
import { onMounted, ref } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import { block, unblock } from "@/libs/utils";

interface LandingContentForm {
    app_name: string;
    description: string;
    email: string;
    whatsapp: string;
    phone: string;
    address: string;
    hero_title: string;
    hero_desc: string;
    cta_primary_label: string;
    cta_primary_url: string;
    cta_secondary_label: string;
    cta_secondary_url: string;
    proof_text: string;
    contact_hero_title: string;
    contact_hero_subtitle: string;
    contact_maps_url: string;
    projects_page_label: string;
    projects_page_title: string;
    projects_page_subtitle: string;
    ceo_name: string;
    ceo_position: string;
    ceo_comment: string;
}

const content = ref<LandingContentForm>({
    app_name: "",
    description: "",
    email: "",
    whatsapp: "",
    phone: "",
    address: "",
    hero_title: "",
    hero_desc: "",
    cta_primary_label: "",
    cta_primary_url: "",
    cta_secondary_label: "",
    cta_secondary_url: "",
    proof_text: "",
    contact_hero_title: "",
    contact_hero_subtitle: "",
    contact_maps_url: "",
    projects_page_label: "",
    projects_page_title: "",
    projects_page_subtitle: "",
    ceo_name: "",
    ceo_position: "",
    ceo_comment: "",
});

const currentLogo = ref<string | null>(null);
const currentCeoPhoto = ref<string | null>(null);
const fileTypes = ref(["image/jpeg", "image/png", "image/jpg", "image/webp"]);
const logoFile = ref<any>([]);
const ceoPhotoFile = ref<any>([]);

const formSchema = Yup.object().shape({
    app_name: Yup.string().required("Nama aplikasi harus diisi"),
    description: Yup.string().nullable(),
    email: Yup.string().email("Email harus valid").nullable(),
    whatsapp: Yup.string().nullable(),
    phone: Yup.string().nullable(),
    address: Yup.string().nullable(),
    hero_title: Yup.string().nullable(),
    hero_desc: Yup.string().nullable(),
    cta_primary_label: Yup.string().nullable(),
    cta_primary_url: Yup.string().nullable(),
    cta_secondary_label: Yup.string().nullable(),
    cta_secondary_url: Yup.string().nullable(),
    proof_text: Yup.string().nullable(),
    contact_hero_title: Yup.string().nullable(),
    contact_hero_subtitle: Yup.string().nullable(),
    contact_maps_url: Yup.string().nullable(),
    projects_page_label: Yup.string().nullable(),
    projects_page_title: Yup.string().nullable(),
    projects_page_subtitle: Yup.string().nullable(),
    ceo_name: Yup.string().nullable(),
    ceo_position: Yup.string().nullable(),
    ceo_comment: Yup.string().nullable(),
});

function fetchContent() {
    block(document.getElementById("form-landing-content"));
    axios
        .get("/master/landing-content")
        .then(({ data }) => {
            const result = data.data ?? {};
            Object.keys(content.value).forEach((key) => {
                if (result[key] !== undefined) {
                    (content.value as any)[key] = result[key];
                }
            });
            currentLogo.value = result.logo ? "/storage/" + result.logo : null;
            currentCeoPhoto.value = result.ceo_photo ? "/storage/" + result.ceo_photo : null;
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? "Gagal memuat landing content");
        })
        .finally(() => {
            unblock(document.getElementById("form-landing-content"));
        });
}

function submit() {
    const formData = new FormData();

    Object.entries(content.value).forEach(([key, value]) => {
        formData.append(key, value ?? "");
    });

    if (logoFile.value.length && logoFile.value[0].file) {
        formData.append("logo", logoFile.value[0].file);
    }
    if (ceoPhotoFile.value.length && ceoPhotoFile.value[0].file) {
    formData.append("ceo_photo", ceoPhotoFile.value[0].file);
}

    block(document.getElementById("form-landing-content"));
    axios
        .post("/master/landing-content", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        })
        .then(() => {
            toast.success("Landing content berhasil disimpan");
            fetchContent();
        })
        .catch((err: any) => {
            if (err.response?.data?.errors) {
                formRef.value?.setErrors(err.response.data.errors);
            }
            toast.error(err.response?.data?.message ?? "Gagal menyimpan landing content");
        })
        .finally(() => {
            unblock(document.getElementById("form-landing-content"));
        });
}

onMounted(fetchContent);
</script>

<template>
    <VForm
        class="form"
        @submit="submit"
        :validation-schema="formSchema"
        id="form-landing-content"
        ref="formRef"
    >
        <!--begin::Info Umum-->
        <div class="card mb-10">
            <div class="card-header align-items-center">
                <h2 class="mb-0">Landing Content - Info Umum</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6 required">Nama Aplikasi</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="app_name"
                                v-model="content.app_name"
                            />
                            <div class="fv-plugins-message-container">
                                <div class="fv-help-block"><ErrorMessage name="app_name" /></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Logo</label>
                            <file-upload
                                :files="logoFile"
                                :accepted-file-types="fileTypes"
                                v-on:updatefiles="(file) => (logoFile = file)"
                            ></file-upload>
                            <img
                                v-if="currentLogo && !logoFile.length"
                                :src="currentLogo"
                                alt="Logo saat ini"
                                style="height: 40px; margin-top: 8px;"
                            />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Deskripsi Perusahaan</label>
                            <Field
                                as="textarea"
                                class="form-control form-control-lg form-control-solid"
                                name="description"
                                rows="3"
                                v-model="content.description"
                            />
                            <div class="fv-plugins-message-container">
                                <div class="fv-help-block"><ErrorMessage name="description" /></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Email</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="email"
                                v-model="content.email"
                            />
                            <div class="fv-plugins-message-container">
                                <div class="fv-help-block"><ErrorMessage name="email" /></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">WhatsApp</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="whatsapp"
                                v-model="content.whatsapp"
                                placeholder="62xxxxxxxxxx"
                            />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Telepon</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="phone"
                                v-model="content.phone"
                            />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Alamat</label>
                            <Field
                                as="textarea"
                                class="form-control form-control-lg form-control-solid"
                                name="address"
                                rows="2"
                                v-model="content.address"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Info Umum-->

        <!--begin::Hero Section-->
        <div class="card mb-10">
            <div class="card-header align-items-center">
                <h2 class="mb-0">Landing Content - Hero Section</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Judul Hero</label>
                            <Field
                                as="textarea"
                                class="form-control form-control-lg form-control-solid"
                                name="hero_title"
                                rows="3"
                                v-model="content.hero_title"
                                placeholder="Boleh multi-baris"
                            />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Deskripsi Hero</label>
                            <Field
                                as="textarea"
                                class="form-control form-control-lg form-control-solid"
                                name="hero_desc"
                                rows="3"
                                v-model="content.hero_desc"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Label Tombol Utama</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="cta_primary_label"
                                v-model="content.cta_primary_label"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">URL Tombol Utama</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="cta_primary_url"
                                v-model="content.cta_primary_url"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Label Tombol Kedua</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="cta_secondary_label"
                                v-model="content.cta_secondary_label"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">URL Tombol Kedua</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="cta_secondary_url"
                                v-model="content.cta_secondary_url"
                            />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Teks Proof / Kepercayaan</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="proof_text"
                                v-model="content.proof_text"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Hero Section-->

        <!--begin::Contact Page-->
        <div class="card mb-10">
            <div class="card-header align-items-center">
                <h2 class="mb-0">Landing Content - Halaman Kontak</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Judul Hero Kontak</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="contact_hero_title"
                                v-model="content.contact_hero_title"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Maps URL</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="contact_maps_url"
                                v-model="content.contact_maps_url"
                            />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Subjudul Hero Kontak</label>
                            <Field
                                as="textarea"
                                class="form-control form-control-lg form-control-solid"
                                name="contact_hero_subtitle"
                                rows="2"
                                v-model="content.contact_hero_subtitle"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Contact Page-->

        <!--begin::Komentar CEO-->
<div class="card mb-10">
    <div class="card-header align-items-center">
        <h2 class="mb-0">Landing Content - Komentar CEO (Beranda)</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="fv-row mb-7">
                    <label class="form-label fw-bold fs-6">Nama CEO</label>
                    <Field
                        class="form-control form-control-lg form-control-solid"
                        type="text"
                        name="ceo_name"
                        v-model="content.ceo_name"
                    />
                </div>
            </div>
            <div class="col-md-6">
                <div class="fv-row mb-7">
                    <label class="form-label fw-bold fs-6">Jabatan CEO</label>
                    <Field
                        class="form-control form-control-lg form-control-solid"
                        type="text"
                        name="ceo_position"
                        v-model="content.ceo_position"
                    />
                </div>
            </div>
            <div class="col-md-12">
                <div class="fv-row mb-7">
                    <label class="form-label fw-bold fs-6">Komentar</label>
                    <Field
                        as="textarea"
                        class="form-control form-control-lg form-control-solid"
                        name="ceo_comment"
                        rows="3"
                        v-model="content.ceo_comment"
                        placeholder="Boleh multi-baris"
                    />
                </div>
            </div>
            <div class="col-md-6">
                <div class="fv-row mb-7">
                    <label class="form-label fw-bold fs-6">Foto CEO</label>
                    <file-upload
                        :files="ceoPhotoFile"
                        :accepted-file-types="fileTypes"
                        v-on:updatefiles="(file) => (ceoPhotoFile = file)"
                    ></file-upload>
                    <img
                        v-if="currentCeoPhoto && !ceoPhotoFile.length"
                        :src="currentCeoPhoto"
                        alt="Foto CEO saat ini"
                        style="height: 80px; margin-top: 8px; border-radius: 50%;"
                    />
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Komentar CEO-->

        <!--begin::Projects Page-->
        <div class="card mb-10">
            <div class="card-header align-items-center">
                <h2 class="mb-0">Landing Content - Halaman Projects</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Label</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="projects_page_label"
                                v-model="content.projects_page_label"
                            />
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Judul</label>
                            <Field
                                class="form-control form-control-lg form-control-solid"
                                type="text"
                                name="projects_page_title"
                                v-model="content.projects_page_title"
                            />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="fv-row mb-7">
                            <label class="form-label fw-bold fs-6">Subjudul</label>
                            <Field
                                as="textarea"
                                class="form-control form-control-lg form-control-solid"
                                name="projects_page_subtitle"
                                rows="2"
                                v-model="content.projects_page_subtitle"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Projects Page-->

        <div class="card">
            <div class="card-footer d-flex">
                <button type="submit" class="btn btn-primary btn-sm ms-auto">
                    Simpan Semua
                </button>
            </div>
        </div>
    </VForm>
</template>