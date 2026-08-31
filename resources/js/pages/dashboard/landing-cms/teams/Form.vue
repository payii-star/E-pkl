<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Team } from "@/types";

const props = defineProps({
    selected: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const team = ref<Team>({
    name: "",
    position: "",
    order: 1,
} as Team);

const fileTypes = ref([
    "image/jpeg",
    "image/png",
    "image/jpg",
]);

const image = ref<any[]>([]);

const formRef = ref();

const formSchema = Yup.object().shape({
    name: Yup.string().required(
        "Nama harus diisi"
    ),

    position: Yup.string().nullable(),

    order: Yup.number()
        .typeError("Urutan harus berupa angka")
        .required("Urutan harus diisi"),
});

function resetForm() {
    team.value = {
        name: "",
        position: "",
        order: 1,
    } as Team;

    image.value = [];
}

function getEdit() {
    if (!props.selected) {
        return;
    }

    block(
        document.getElementById("form-team")
    );

    axios
        .get(`/master/teams/${props.selected}`)
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
             *     team: {...}
             * }
             */
            const teamData =
                data.data ??
                data.team ??
                data;

            team.value = teamData;

            image.value = teamData.image
                ? ["/storage/" + teamData.image]
                : [];
        })
        .catch((err: any) => {
            toast.error(
                err.response?.data?.message ??
                    "Gagal memuat data team"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById("form-team")
            );
        });
}

function submit() {
    const formData = new FormData();

    formData.append(
        "name",
        team.value.name ?? ""
    );

    formData.append(
        "position",
        team.value.position ?? ""
    );

    formData.append(
        "order",
        String(team.value.order ?? 1)
    );

    /*
     * Hanya kirim foto jika user memilih
     * file baru.
     */
    if (
        image.value.length &&
        image.value[0]?.file
    ) {
        formData.append(
            "image",
            image.value[0].file
        );
    }

    /*
     * CREATE
     * POST /api/master/teams
     *
     * EDIT
     * PUT /api/master/teams/{id}
     *
     * Karena menggunakan multipart/form-data,
     * edit dikirim POST + _method=PUT.
     */
    if (props.selected) {
        formData.append("_method", "PUT");
    }

    block(
        document.getElementById("form-team")
    );

    axios({
        method: "post",

        url: props.selected
            ? `/master/teams/${props.selected}`
            : "/master/teams",

        data: formData,

        headers: {
            "Content-Type":
                "multipart/form-data",
        },
    })
        .then(() => {
            toast.success(
                props.selected
                    ? "Team berhasil diperbarui"
                    : "Team berhasil ditambahkan"
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
                    "Gagal menyimpan team"
            );
        })
        .finally(() => {
            unblock(
                document.getElementById("form-team")
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
        id="form-team"
        ref="formRef"
    >
        <div
            class="card-header align-items-center"
        >
            <h2 class="mb-0">
                {{ selected ? "Edit" : "Tambah" }}
                Team Member
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
                            v-model="team.name"
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

                <!-- JABATAN -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Jabatan
                        </label>

                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="position"
                            autocomplete="off"
                            v-model="team.position"
                            placeholder="Contoh: Project Manager"
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

                <!-- URUTAN -->
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
                            v-model="team.order"
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

                <!-- FOTO -->
                <div class="col-md-6">
                    <div class="fv-row mb-7">
                        <label
                            class="form-label fw-bold fs-6"
                        >
                            Foto
                        </label>

                        <file-upload
                            :files="image"
                            :accepted-file-types="
                                fileTypes
                            "
                            v-on:updatefiles="
                                (file) =>
                                    (image = file)
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
                                    name="image"
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