<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Menu } from "@/types";

const props = defineProps({
    selected: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const menu = ref<Menu>({
    name: "",
    url: "",
    order: 1,
} as Menu);

const formRef = ref();

const formSchema = Yup.object().shape({
    name: Yup.string().required(
        "Nama menu harus diisi"
    ),

    url: Yup.string().required(
        "URL harus diisi"
    ),

    order: Yup.number()
        .typeError("Urutan harus berupa angka")
        .required("Urutan harus diisi"),
});

function resetForm() {
    menu.value = {
        name: "",
        url: "",
        order: 1,
    } as Menu;
}

function getEdit() {
    if (!props.selected) {
        return;
    }

    block(document.getElementById("form-menu"));

    axios
        .get(`/master/menu/${props.selected}`)
        .then(({ data }) => {
            /*
             * Backend bisa mengembalikan:
             * {
             *     data: {...}
             * }
             *
             * atau:
             * {
             *     menu: {...}
             * }
             *
             * Kita dukung keduanya.
             */
            menu.value = data.data ?? data.menu ?? data;
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ??
                    "Gagal memuat data menu"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById("form-menu")
            );
        });
}

function submit() {
    const payload = {
        name: menu.value.name,
        url: menu.value.url,
        order: Number(menu.value.order),
    };

    block(document.getElementById("form-menu"));

    axios({
        /*
         * CREATE
         * POST /api/master/menu
         *
         * EDIT
         * PUT /api/master/menu/{id}
         */
        method: props.selected ? "put" : "post",

        url: props.selected
            ? `/master/menu/${props.selected}`
            : "/master/menu",

        data: payload,
    })
        .then(() => {
            toast.success(
                props.selected
                    ? "Menu berhasil diperbarui"
                    : "Menu berhasil ditambahkan"
            );

            emit("close");
            emit("refresh");

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
                    "Gagal menyimpan menu"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById("form-menu")
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
        id="form-menu"
        ref="formRef"
    >
        <div class="card-header align-items-center">
            <h2 class="mb-0">
                {{ selected ? "Edit" : "Tambah" }} Menu
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
                <!-- Nama Menu -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            Nama Menu
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="name"
                            autocomplete="off"
                            v-model="menu.name"
                            placeholder="Masukkan Nama Menu"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="name" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- URL -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6 required"
                        >
                            URL
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="url"
                            autocomplete="off"
                            v-model="menu.url"
                            placeholder="/tentang-kami"
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
                            v-model="menu.order"
                            placeholder="1"
                        />

                        <div
                            class="fv-plugins-message-container"
                        >
                            <div class="fv-help-block">
                                <ErrorMessage name="order" />
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