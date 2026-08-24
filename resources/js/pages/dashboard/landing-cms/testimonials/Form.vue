<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Testimonial } from "@/types";

const props = defineProps({
    selected: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const testimonial = ref<Testimonial>({
    name: "",
    position: "",
    message: "",
    placement: "",
} as Testimonial);

const fileTypes = ref([
    "image/jpeg",
    "image/png",
    "image/jpg",
]);

const photo = ref<any[]>([]);

const formRef = ref();

const formSchema = Yup.object().shape({
    name: Yup.string().required(
        "Nama harus diisi"
    ),

    position: Yup.string().required(
        "Jabatan / instansi harus diisi"
    ),

    message: Yup.string().required(
        "Isi testimoni harus diisi"
    ),

    placement: Yup.string().required(
        "Pilih tempat tampil"
    ),
});

function resetForm() {
    testimonial.value = {
        name: "",
        position: "",
        message: "",
        placement: "",
    } as Testimonial;

    photo.value = [];
}

function getEdit() {
    if (!props.selected) {
        return;
    }

    block(
        document.getElementById(
            "form-testimonial"
        )
    );

    axios
        .get(
            `/master/testimonials/${props.selected}`
        )
        .then(({ data }) => {
            /*
             * Support response:
             *
             * {
             *     data: {...}
             * }
             *
             * atau:
             *
             * {
             *     testimonial: {...}
             * }
             */
            const testimonialData =
                data.data ??
                data.testimonial ??
                data;

            testimonial.value =
                testimonialData;

            photo.value =
                testimonialData.photo
                    ? [
                          "/storage/" +
                              testimonialData.photo,
                      ]
                    : [];
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ??
                    "Gagal memuat data testimonial"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById(
                    "form-testimonial"
                )
            );
        });
}

function submit(values: any) {
    // ── DEBUG SEMENTARA — hapus 2 baris ini setelah masalah ketemu ──
    console.log("DEBUG values (dari vee-validate):", values);
    console.log("DEBUG testimonial.value (dari ref manual):", testimonial.value);
    // ──────────────────────────────────────────────────────────────

    const formData = new FormData();

    formData.append("name", values.name ?? "");
    formData.append("position", values.position ?? "");
    formData.append("message", values.message ?? "");
    formData.append("placement", values.placement ?? "");

    /*
     * Hanya upload foto jika user memilih
     * file baru.
     */
    if (
        photo.value.length &&
        photo.value[0]?.file
    ) {
        formData.append(
            "photo",
            photo.value[0].file
        );
    }

    /*
     * CREATE
     * POST /api/master/testimonials
     *
     * EDIT
     * PUT /api/master/testimonials/{id}
     *
     * Karena menggunakan FormData untuk upload,
     * edit dikirim sebagai POST + _method=PUT.
     */
    if (props.selected) {
        formData.append("_method", "PUT");
    }

    block(
        document.getElementById(
            "form-testimonial"
        )
    );

    axios({
        method: "post",

        url: props.selected
            ? `/master/testimonials/${props.selected}`
            : "/master/testimonials",

        data: formData,

        headers: {
            "Content-Type":
                "multipart/form-data",
        },
    })
        .then(() => {
            toast.success(
                props.selected
                    ? "Testimonial berhasil diperbarui"
                    : "Testimonial berhasil ditambahkan"
            );

            emit("close");
            emit("refresh");

            formRef.value?.resetForm();
        })
        .catch((err: any) => {
            if (
                err.response?.data?.errors
            ) {
                formRef.value?.setErrors(
                    err.response.data.errors
                );
            }

            toast.error(
                err.response?.data?.message ??
                    "Gagal menyimpan testimonial"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById(
                    "form-testimonial"
                )
            );
        });
}

onMounted(() => {
    if (props.selected) {
        getEdit();
    } else {
        resetForm();
    }
});

watch(
    () => props.selected,
    () => {
        if (props.selected) {
            getEdit();
        } else {
            resetForm();
        }
    }
);
</script>

<template>
    <VForm
        class="form card mb-10"
        @submit="submit"
        :validation-schema="formSchema"
        id="form-testimonial"
        ref="formRef"
    >
        <div
            class="card-header align-items-center"
        >
            <h2 class="mb-0">
                {{
                    selected
                        ? "Edit"
                        : "Tambah"
                }}
                Testimonial
            </h2>

            <button
                type="button"
                class="btn btn-sm btn-light-danger ms-auto"
                @click="emit('close')"
            >
                Batal

                <i
                    class="la la-times-circle p-0"
                ></i>
            </button>
        </div>

        <div class="card-body">
            <div class="row">
                <!-- NAMA -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Nama
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="name"
                            autocomplete="off"
                            v-model="
                                testimonial.name
                            "
                            placeholder="Masukkan Nama"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="name"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- JABATAN / INSTANSI -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Jabatan / Instansi
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="position"
                            autocomplete="off"
                            v-model="
                                testimonial.position
                            "
                            placeholder="Contoh: Kepala Dinas ABC"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="position"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TESTIMONI -->
                <div class="col-md-12">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Isi Testimoni
                        </label>

                        <Field
                            as="textarea"
                            class="form-control form-control-lg form-control-solid"
                            rows="4"
                            name="message"
                            autocomplete="off"
                            v-model="
                                testimonial.message
                            "
                            placeholder="Masukkan isi testimoni"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="message"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PLACEMENT -->
                <div class="col-md-12">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Tampilkan di
                        </label>

                        <Field
                            as="select"
                            class="form-select form-select-lg form-select-solid"
                            name="placement"
                            v-model="
                                testimonial.placement
                            "
                        >
                            <option
                                value=""
                                disabled
                            >
                                Pilih lokasi tampil
                            </option>

                            <option value="services">
                                Halaman Layanan
                                (Klien)
                            </option>

                            <option value="beranda">
                                Beranda
                                (Testimoni CEO)
                            </option>
                        </Field>

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="placement"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOTO -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Foto
                        </label>

                        <file-upload
                            :files="photo"
                            :accepted-file-types="
                                fileTypes
                            "
                            v-on:updatefiles="
                                (file) =>
                                    (photo = file)
                            "
                        >
                        </file-upload>

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="photo"
                                />
                            </div>
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