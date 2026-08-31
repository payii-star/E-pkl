<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Service } from "@/types";

const props = defineProps({
    selected: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const service = ref<Service>({
    title: "",
    description: "",
    order: 1,
} as Service);

const fileTypes = ref([
    "image/jpeg",
    "image/png",
    "image/jpg",
    "image/svg+xml",
]);

const icon = ref<any[]>([]);

const formRef = ref();

const formSchema = Yup.object().shape({
    title: Yup.string().required(
        "Judul layanan harus diisi"
    ),

    description: Yup.string().required(
        "Deskripsi harus diisi"
    ),

    order: Yup.number()
        .typeError("Urutan harus berupa angka")
        .required("Urutan harus diisi"),
});

function resetForm() {
    service.value = {
        title: "",
        description: "",
        order: 1,
    } as Service;

    icon.value = [];
}

function getEdit() {
    if (!props.selected) {
        return;
    }

    block(
        document.getElementById("form-service")
    );

    axios
        .get(`/master/services/${props.selected}`)
        .then(({ data }) => {
            /*
             * Support beberapa bentuk response API:
             *
             * {
             *     data: {...}
             * }
             *
             * atau:
             *
             * {
             *     service: {...}
             * }
             */
            const serviceData =
                data.data ??
                data.service ??
                data;

            service.value = serviceData;

            icon.value = serviceData.icon
                ? ["/storage/" + serviceData.icon]
                : [];
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ??
                    "Gagal memuat data layanan"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById(
                    "form-service"
                )
            );
        });
}

function submit() {
    const formData = new FormData();

    formData.append(
        "title",
        service.value.title ?? ""
    );

    formData.append(
        "description",
        service.value.description ?? ""
    );

    formData.append(
        "order",
        String(service.value.order ?? 1)
    );

    /*
     * Upload icon hanya jika user memilih
     * file baru.
     */
    if (
        icon.value.length &&
        icon.value[0]?.file
    ) {
        formData.append(
            "icon",
            icon.value[0].file
        );
    }

    /*
     * Backend Service:
     *
     * CREATE
     * POST /api/master/services
     *
     * EDIT
     * PUT /api/master/services/{id}
     *
     * Karena upload file menggunakan FormData,
     * request tetap dikirim sebagai POST dan
     * Laravel method spoofing digunakan untuk edit.
     */
    if (props.selected) {
        formData.append("_method", "PUT");
    }

    block(
        document.getElementById("form-service")
    );

    axios({
        method: "post",

        url: props.selected
            ? `/master/services/${props.selected}`
            : "/master/services",

        data: formData,

        headers: {
            "Content-Type":
                "multipart/form-data",
        },
    })
        .then(() => {
            toast.success(
                props.selected
                    ? "Layanan berhasil diperbarui"
                    : "Layanan berhasil ditambahkan"
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
                    "Gagal menyimpan layanan"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById(
                    "form-service"
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
        id="form-service"
        ref="formRef"
    >
        <div
            class="card-header align-items-center"
        >
            <h2 class="mb-0">
                {{ selected ? "Edit" : "Tambah" }}
                Service
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
                <!-- Judul -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Judul Layanan
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="title"
                            autocomplete="off"
                            v-model="service.title"
                            placeholder="Masukkan Judul Layanan"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="title"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Urutan -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Urutan
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="number"
                            name="order"
                            autocomplete="off"
                            v-model="service.order"
                            placeholder="1"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="order"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deskripsi -->
                <div class="col-md-12">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Deskripsi
                        </label>

                        <Field
                            as="textarea"
                            class="form-control form-control-lg form-control-solid"
                            rows="4"
                            name="description"
                            autocomplete="off"
                            v-model="
                                service.description
                            "
                            placeholder="Masukkan Deskripsi Layanan"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div
                                class="fv-help-block"
                            >
                                <ErrorMessage
                                    name="description"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Icon -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Icon / Gambar Layanan
                        </label>

                        <file-upload
                            :files="icon"
                            :accepted-file-types="
                                fileTypes
                            "
                            v-on:updatefiles="
                                (file) =>
                                    (icon = file)
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
                                    name="icon"
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