<script setup lang="ts">
import { onMounted, ref } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import Swal from "sweetalert2";
import { block, unblock } from "@/libs/utils";
import type { FooterSetting, FooterSocial } from "@/types";

// ===== Company Info =====
const footer = ref<FooterSetting>({
    company_name: "",
    description: "",
    address: "",
    email: "",
    phone: "",
    copyright: "",
});

const formSchema = Yup.object().shape({
    company_name: Yup.string().required("Nama perusahaan harus diisi"),
    description: Yup.string().required("Deskripsi harus diisi"),
    address: Yup.string().nullable(),
    email: Yup.string().email("Email harus valid").nullable(),
    phone: Yup.string().nullable(),
    copyright: Yup.string().nullable(),
});

function submitFooter() {
    block(document.getElementById("form-footer-setting"));
    axios
        .post("/master/footer/setting", footer.value)
        .then(({ data }) => {
            footer.value = data.data;
            toast.success("Info perusahaan berhasil disimpan");
        })
        .catch((err: any) => {
            if (err.response?.data?.errors) {
                footerFormRef.value?.setErrors(err.response.data.errors);
            }
            toast.error(err.response?.data?.message ?? "Gagal menyimpan info perusahaan");
        })
        .finally(() => {
            unblock(document.getElementById("form-footer-setting"));
        });
}

const footerFormRef = ref();

function fetchFooter() {
    axios
        .get("/master/footer")
        .then(({ data }) => {
            if (data.setting) footer.value = data.setting;
            socials.value = data.socials ?? [];
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? "Gagal memuat data footer");
        });
}

// ===== Socials =====
const socials = ref<FooterSocial[]>([]);

const socialForm = ref<FooterSocial>({ platform: "", url: "" });
const editingId = ref<number | null>(null);
const showSocialForm = ref<boolean>(false);

function addSocial() {
    socialForm.value = { platform: "", url: "" };
    editingId.value = null;
    showSocialForm.value = true;
}

function editSocial(social: FooterSocial) {
    socialForm.value = { ...social };
    editingId.value = social.id ?? null;
    showSocialForm.value = true;
}

function cancelSocialForm() {
    showSocialForm.value = false;
    socialForm.value = { platform: "", url: "" };
    editingId.value = null;
}

function submitSocial() {
    if (!socialForm.value.platform || !socialForm.value.url) {
        toast.error("Platform dan URL wajib diisi");
        return;
    }

    const request = editingId.value
        ? axios.put(`/master/footer/socials/${editingId.value}`, socialForm.value)
        : axios.post("/master/footer/socials/store", socialForm.value);

    request
        .then(() => {
            toast.success("Social media berhasil disimpan");
            cancelSocialForm();
            fetchFooter();
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? "Gagal menyimpan social media");
        });
}

function removeSocial(social: FooterSocial) {
    Swal.fire({
        title: "Apakah anda yakin?",
        text: `Hapus social "${social.platform}"?`,
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
            .delete(`/master/footer/socials/${social.id}`)
            .then(() => {
                toast.success("Social media berhasil dihapus");
                fetchFooter();
            })
            .catch((err: any) => {
                toast.error(err.response?.data?.message ?? "Gagal menghapus social media");
            });
    });
}

onMounted(fetchFooter);
</script>

<template>
    <!--begin::Company Info-->
    <VForm
        class="form card mb-10"
        @submit="submitFooter"
        :validation-schema="formSchema"
        id="form-footer-setting"
        ref="footerFormRef"
    >
        <div class="card-header align-items-center">
            <h2 class="mb-0">Footer - Info Perusahaan</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Nama Perusahaan
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="company_name"
                            autocomplete="off"
                            v-model="footer.company_name"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="company_name" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Email
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="email"
                            autocomplete="off"
                            v-model="footer.email"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="email" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Deskripsi
                        </label>
                        <Field
                            as="textarea"
                            class="form-control form-control-lg form-control-solid"
                            name="description"
                            rows="2"
                            v-model="footer.description"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="description" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Alamat
                        </label>
                        <Field
                            as="textarea"
                            class="form-control form-control-lg form-control-solid"
                            name="address"
                            rows="2"
                            v-model="footer.address"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="address" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Telepon
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="phone"
                            autocomplete="off"
                            v-model="footer.phone"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="phone" />
                            </div>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Copyright
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="copyright"
                            autocomplete="off"
                            v-model="footer.copyright"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="copyright" />
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
    <!--end::Company Info-->

    <!--begin::Socials-->
    <div class="card">
        <div class="card-header align-items-center">
            <h2 class="mb-0">Footer - Social Media</h2>
            <button
                type="button"
                class="btn btn-sm btn-primary ms-auto"
                v-if="!showSocialForm"
                @click="addSocial"
            >
                Tambah
                <i class="la la-plus"></i>
            </button>
        </div>
        <div class="card-body">
            <!--begin::Mini add/edit form-->
            <div
                v-if="showSocialForm"
                class="border rounded p-5 mb-6 bg-light-secondary bg-opacity-25"
            >
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold fs-7">
                            Platform
                        </label>
                        <input
                            type="text"
                            class="form-control form-control-solid"
                            v-model="socialForm.platform"
                            placeholder="Instagram, Facebook, dll"
                        />
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold fs-7">URL</label>
                        <input
                            type="text"
                            class="form-control form-control-solid"
                            v-model="socialForm.url"
                            placeholder="https://..."
                        />
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-primary"
                            @click="submitSocial"
                        >
                            Simpan
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-light"
                            @click="cancelSocialForm"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </div>
            <!--end::Mini add/edit form-->

            <div class="table-responsive">
                <table
                    class="table table-rounded table-hover table-striped border gy-7 gs-7"
                >
                    <thead class="bg-gray-200">
                        <tr
                            class="fw-bolder fs-6 text-gray-800 border-bottom border-gray-200"
                        >
                            <th class="py-4">#</th>
                            <th class="py-4">Platform</th>
                            <th class="py-4">URL</th>
                            <th class="py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="socials.length">
                            <tr
                                v-for="(social, index) in socials"
                                :key="social.id"
                            >
                                <td class="py-4">{{ index + 1 }}</td>
                                <td class="py-4">{{ social.platform }}</td>
                                <td class="py-4">{{ social.url }}</td>
                                <td class="py-4">
                                    <div class="d-flex gap-2">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-icon btn-info"
                                            @click="editSocial(social)"
                                        >
                                            <i class="la la-pencil fs-2"></i>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-icon btn-danger"
                                            @click="removeSocial(social)"
                                        >
                                            <i class="la la-trash fs-2"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-else>
                            <td colspan="4" class="text-center py-4">
                                Belum ada social media
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!--end::Socials-->
</template>