<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Project } from "@/types";

const props = defineProps({
    selected: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const project = ref<Project>({
    title: "",
    description: "",
    url: "",
    category: "web",
    is_featured: false,
    urutan: 1,
} as Project);

const fileTypes = ref(["image/jpeg", "image/png", "image/jpg"]);
const thumbnail = ref<any>([]);
const formRef = ref();

const formSchema = Yup.object().shape({
    title: Yup.string().required("Title harus diisi"),
    description: Yup.string().nullable(),
    url: Yup.string().url("URL harus valid").nullable(),
    category: Yup.string()
        .oneOf(["web", "mobile"], "Kategori tidak valid")
        .required("Kategori harus dipilih"),
    urutan: Yup.number()
        .typeError("Urutan harus angka")
        .required("Urutan harus diisi"),
});

function getEdit() {
    block(document.getElementById("form-project"));
    axios
        .get(`/master/projects/${props.selected}`)
        .then(({ data }) => {
            project.value = {
                ...data.data,
                url: data.data.link_project,
                category: data.data.category ?? "web",
            };
            thumbnail.value = data.data.image ? ["/storage/" + data.data.image] : [];
        })
        .catch((err: any) => {
            toast.error(err.response?.data?.message ?? "Gagal memuat data");
        })
        .finally(() => {
            unblock(document.getElementById("form-project"));
        });
}

function submit() {
    const formData = new FormData();
    formData.append("title", project.value.title);
    formData.append("description", project.value.description ?? "");
    formData.append("url", project.value.url ?? "");
    formData.append("category", project.value.category ?? "web");
    formData.append("urutan", String(project.value.urutan));
    formData.append("is_featured", project.value.is_featured ? "1" : "0");

    if (thumbnail.value.length && thumbnail.value[0].file) {
        formData.append("thumbnail", thumbnail.value[0].file);
    }
    if (props.selected) {
        formData.append("_method", "PUT");
    }

    block(document.getElementById("form-project"));
    axios({
        method: "post",
        url: props.selected ? `/master/projects/${props.selected}` : "/master/projects/store",
        data: formData,
        headers: { "Content-Type": "multipart/form-data" },
    })
        .then(() => {
            emit("close");
            emit("refresh");
            toast.success("Project berhasil disimpan");
            formRef.value.resetForm();
        })
        .catch((err: any) => {
            if (err.response?.data?.errors) {
                formRef.value.setErrors(err.response.data.errors);
            }
            toast.error(err.response?.data?.message ?? "Gagal menyimpan project");
        })
        .finally(() => {
            unblock(document.getElementById("form-project"));
        });
}

watch(
    () => props.selected,
    () => {
        if (props.selected) {
            getEdit();
        }
    },
    { immediate: true }
);
</script>

<template>
    <VForm
        class="form card mb-10"
        @submit="submit"
        :validation-schema="formSchema"
        id="form-project"
        ref="formRef"
    >
        <div class="card-header align-items-center">
            <h2 class="mb-0">{{ selected ? "Edit" : "Tambah" }} Project</h2>
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
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Title
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="title"
                            autocomplete="off"
                            v-model="project.title"
                            placeholder="Masukkan judul project"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="title" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-6">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            URL Project
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="url"
                            autocomplete="off"
                            v-model="project.url"
                            placeholder="https://..."
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="url" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-12">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Deskripsi
                        </label>
                        <Field
                            as="textarea"
                            class="form-control form-control-lg form-control-solid"
                            name="description"
                            rows="3"
                            v-model="project.description"
                            placeholder="Deskripsi singkat project"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="description" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-6">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Thumbnail
                        </label>
                        <!--begin::Input-->
                        <file-upload
                            :files="thumbnail"
                            :accepted-file-types="fileTypes"
                            v-on:updatefiles="(file) => (thumbnail = file)"
                        ></file-upload>
                        <!--end::Input-->
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="thumbnail" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-3">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Kategori
                        </label>
                        <Field
                            as="select"
                            class="form-select form-select-lg form-select-solid"
                            name="category"
                            v-model="project.category"
                        >
                            <option value="web">Web</option>
                            <option value="mobile">Mobile</option>
                        </Field>
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="category" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-3">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Urutan
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="number"
                            name="urutan"
                            autocomplete="off"
                            v-model="project.urutan"
                            placeholder="1"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="urutan" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-3">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Featured
                        </label>
                        <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                            <Field
                                class="form-check-input"
                                type="checkbox"
                                name="is_featured"
                                v-model="project.is_featured"
                                :value="true"
                                :unchecked-value="false"
                            />
                            <label class="form-check-label">
                                {{ project.is_featured ? "Ya" : "Tidak" }}
                            </label>
                        </div>
                    </div>
                    <!--end::Input group-->
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
