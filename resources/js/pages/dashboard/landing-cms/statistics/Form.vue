<script setup lang="ts">
import { block, unblock } from "@/libs/utils";
import { onMounted, ref, watch } from "vue";
import * as Yup from "yup";
import axios from "@/libs/axios";
import { toast } from "vue3-toastify";
import type { Statistic } from "@/types";

const props = defineProps({
    selected: {
        type: Object as () => Statistic | null,
        default: null,
    },
});

const emit = defineEmits(["close", "refresh"]);

const statistic = ref<Statistic>({
    icon: "",
    statistic: "",
    label: "",
    urutan: 0,
    is_active: true,
} as Statistic);

const formRef = ref();

const formSchema = Yup.object().shape({
    icon: Yup.string().nullable(),
    statistic: Yup.string().required("Nilai statistik harus diisi"),
    label: Yup.string().required("Label harus diisi"),
    urutan: Yup.number()
        .typeError("Urutan harus berupa angka")
        .required("Urutan harus diisi"),
});

function fillFromSelected() {
    if (!props.selected) return;
    statistic.value = { ...props.selected };
}

function resetForm() {
    statistic.value = {
        icon: "",
        statistic: "",
        label: "",
        urutan: 0,
        is_active: true,
    } as Statistic;
}

function submit() {
    const payload = {
        icon: statistic.value.icon,
        statistic: statistic.value.statistic,
        label: statistic.value.label,
        urutan: Number(statistic.value.urutan),
        is_active: !!statistic.value.is_active,
    };

    block(document.getElementById("form-statistic"));
    axios({
        method: props.selected ? "put" : "post",
        url: props.selected ? `/master/statistics/${props.selected.id}` : "/master/statistics/store",
        data: payload,
    })
        .then(() => {
            toast.success("Statistik berhasil disimpan");
            formRef.value?.resetForm();
            emit("close");
            emit("refresh");
        })
        .catch((err: any) => {
            if (err.response?.data?.errors) {
                formRef.value.setErrors(err.response.data.errors);
            }
            toast.error(err.response?.data?.message ?? "Gagal menyimpan statistik");
        })
        .finally(() => {
            unblock(document.getElementById("form-statistic"));
        });
}

onMounted(() => {
    if (props.selected) {
        fillFromSelected();
    }
});

watch(
    () => props.selected,
    () => {
        if (props.selected) {
            fillFromSelected();
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
        id="form-statistic"
        ref="formRef"
    >
        <div class="card-header align-items-center">
            <h2 class="mb-0">{{ selected ? "Edit" : "Tambah" }} Statistik</h2>
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
                <div class="col-md-4">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6">
                            Icon (Font Awesome)
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="icon"
                            autocomplete="off"
                            v-model="statistic.icon"
                            placeholder="cth: briefcase, users, calendar"
                        />
                        <div class="form-text">
                            Nama ikon Font Awesome tanpa prefix "fa-", cth:
                            "briefcase"
                        </div>
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="icon" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-4">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Nilai Statistik
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="statistic"
                            autocomplete="off"
                            v-model="statistic.statistic"
                            placeholder="cth: 10+"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="statistic" />
                            </div>
                        </div>
                    </div>
                    <!--end::Input group-->
                </div>
                <div class="col-md-4">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <label class="form-label fw-bold fs-6 required">
                            Label
                        </label>
                        <Field
                            class="form-control form-control-lg form-control-solid"
                            type="text"
                            name="label"
                            autocomplete="off"
                            v-model="statistic.label"
                            placeholder="cth: Proyek Selesai"
                        />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="label" />
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
                            v-model="statistic.urutan"
                            placeholder="0"
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
                        <label class="form-label fw-bold fs-6 d-block">
                            Aktif?
                        </label>
                        <div class="form-check form-check-solid form-switch">
                            <Field
                                class="form-check-input w-45px h-30px"
                                type="checkbox"
                                name="is_active"
                                :value="true"
                                v-model="statistic.is_active"
                            />
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