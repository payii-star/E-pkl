<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Project } from "@/types";

const MAX_GALLERY = 6;

const props = defineProps({
    selected: {
        type: Number,
        default: null,
    },
}); 

const emit = defineEmits(["close", "refresh"]);

const project = ref<Project>({
    title: "",
    client_name: "",
    description: "",
    url: "",
    category: "web",
    is_featured: false,
    urutan: 1,
} as Project);

const fileTypes = ref(["image/jpeg", "image/png", "image/jpg"]);
const thumbnail = ref<any[]>([]);
const hadInitialThumbnail = ref(false);

// ── GALERI FOTO (maks 6) ─────────────────────────────────────────
// Item bisa berupa:
// - string "/storage/landing/projects/gallery/xxx.jpg" -> foto lama (sudah tersimpan)
// - object dari FilePond yang punya properti .file -> foto baru yang mau diupload
const galleryFiles = ref<any[]>([]);

const formRef = ref();

const formSchema = Yup.object().shape({
    title: Yup.string().required("Title harus diisi"),
    client_name: Yup.string().nullable(),
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
            // PENTING: jangan timpa "url" dengan field yang tidak ada di
            // response (misal "link_project"). Backend mengirim field
            // "url" langsung -- cukup spread ...data.data saja.
            project.value = {
                ...data.data,
                category: data.data.category ?? "web",
            };

            hadInitialThumbnail.value = !!data.data.thumbnail;

            thumbnail.value = data.data.thumbnail
                ? ["/storage/" + data.data.thumbnail]
                : [];

            galleryFiles.value = Array.isArray(data.data.gallery)
                ? data.data.gallery.map((path: string) => "/storage/" + path)
                : [];
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ?? "Gagal memuat data"
            );
        })
        .finally(() => {
            unblock(document.getElementById("form-project"));
       });
}

function handleRemoveThumbnail() {
    thumbnail.value = [];
}

function submit() {
    const formData = new FormData();

    formData.append("title", project.value.title);
    formData.append(
        "client_name",
        project.value.client_name ?? ""
    );
    formData.append(
        "description",
        project.value.description ?? ""
    );
    formData.append("url", project.value.url ?? "");
    formData.append(
        "category",
        project.value.category ?? "web"
    );
    formData.append(
        "urutan",
        String(project.value.urutan)
    );
    formData.append(
        "is_featured",
        project.value.is_featured ? "1" : "0"
    );

    if (
        thumbnail.value.length &&
        thumbnail.value[0]?.file
    ) {
        formData.append(
            "thumbnail",
            thumbnail.value[0].file
        );
    } else if (hadInitialThumbnail.value && thumbnail.value.length === 0) {
        formData.append("remove_thumbnail", "1");
    }

    // ── GALERI: pisahkan foto lama yang dipertahankan vs file baru ──
    if (props.selected) {
        galleryFiles.value.forEach((item: any) => {
            if (item?.file) {
                formData.append("gallery_new[]", item.file);
            } else if (typeof item === "string") {
                formData.append(
                    "gallery_existing[]",
                    item.replace(/^\/storage\//, "")
                );
            } else if (item?.source && typeof item.source === "string") {
                formData.append(
                    "gallery_existing[]",
                    item.source.replace(/^\/storage\//, "")
                );
            }
        });
    } else {
        // Project baru -> semua item di galeri pasti file baru
        galleryFiles.value.forEach((item: any) => {
            if (item?.file) {
                formData.append("gallery[]", item.file);
            }
        });
    }

    block(document.getElementById("form-project"));

    axios({
        method: "post",
        url: props.selected
            ? `/master/projects/${props.selected}`
            : "/master/projects",
        data: formData,
        headers: {
            "Content-Type": "multipart/form-data",
        },
    })
        .then(() => {
            emit("close");
            emit("refresh");

            toast.success("Project berhasil disimpan");

            formRef.value?.resetForm();
        })
        .catch((err: any) => {
            if (err.response?.data?.errors) {
                formRef.value?.setErrors(
                    err.response.data.errors
                );
            }

            toast.error(
                err.response?.data?.message ??
                    "Gagal menyimpan project"
            );
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
            <h2 class="mb-0">
                {{ selected ? "Edit" : "Tambah" }} Project
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
                <!-- Title -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
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

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="title" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- URL -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
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

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="url" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Client / Instansi -->
                <div class="col-md-12">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Client / Instansi
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="client_name"
                            autocomplete="off"
                            v-model="project.client_name"
                            placeholder="Contoh: Pemerintah Daerah Kabupaten Halmahera Timur"
                        />

                        <div class="form-text text-muted fs-7 mt-1">
                            Teks singkat ini yang ditampilkan di kartu daftar project.
                            Deskripsi lengkap di bawah hanya muncul di halaman detail.
                        </div>

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="client_name" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="col-md-12">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
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

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage
                                    name="description"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Thumbnail -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Thumbnail
                        </label>

                        <file-upload
                            :files="thumbnail"
                            :accepted-file-types="fileTypes"
                            v-on:updatefiles="
                                (file) => (thumbnail = file)
                            "
                        >
                        </file-upload>
			<div class="form-text text-muted fs-7 mt-1">
    				Format yang didukung: JPG, JPEG, PNG, WEBP. Ukuran maksimal 2MB.
			</div>
                        <button
                            v-if="thumbnail.length"
                            type="button"
                            class="btn btn-sm btn-light-danger mt-2"
                            @click="handleRemoveThumbnail"
                        >
                            Hapus Thumbnail
                        </button>

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage
                                    name="thumbnail"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Galeri Foto -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Galeri Foto (maks {{ MAX_GALLERY }})
                        </label>

                        <file-upload
                            :files="galleryFiles"
                            :accepted-file-types="fileTypes"
                            :allow-multiple="true"
                            :max-files="MAX_GALLERY"
                            v-on:updatefiles="
                                (files) => (galleryFiles = files)
                            "
                        >
                        </file-upload>

                        <div class="form-text text-muted fs-7 mt-1">
                            Foto tambahan yang ditampilkan di halaman detail project.
                            Format JPG, JPEG, PNG, WEBP. Maksimal {{ MAX_GALLERY }} foto,
                            masing-masing maksimal 2MB.
                        </div>

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage
                                    name="gallery"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category -->
                <div class="col-md-3">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Kategori
                        </label>

                        <Field
                            as="select"
                            class="form-select form-select-lg form-select-solid"
                            name="category"
                            v-model="project.category"
                        >
                            <option value="web">
                                Web
                            </option>

                            <option value="mobile">
                                Mobile
                            </option>
                        </Field>

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="category" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Urutan -->
                <div class="col-md-3">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
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

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="urutan" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Featured -->
                <div class="col-md-3">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Featured
                        </label>

                        <div
                            class="form-check form-switch form-check-custom form-check-solid mt-3"
                        >
                            <Field
                                class="form-check-input"
                                type="checkbox"
                                name="is_featured"
                                v-model="project.is_featured"
                                :value="true"
                                :unchecked-value="false"
                            />

                            <label class="form-check-label">
                                {{
                                    project.is_featured
                                        ? "Ya"
                                        : "Tidak"
                                }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex">
            <button
                type="submit"
                class="btn btn-primary btn-sm ms-auto"
            >
                Simpan
            </button>
        </div>
    </VForm>
</template>