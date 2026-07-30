<template>
    <div class="modal fade" id="kt_modal_promo" ref="promoModalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
        <div class="modal-header">
            <h2 class="fw-bold">{{ isEditMode ? 'Edit' : 'Tambah' }} Promo</h2>
            <div data-bs-dismiss="modal" class="btn btn-icon btn-sm btn-active-icon-primary">
            <KTIcon icon-name="cross" icon-class="fs-1" />
            </div>
        </div>
        <div class="modal-body py-10 px-lg-17">
            <VForm ref="formRef" class="form" @submit="submit" :validation-schema="validationSchema">
            <div class="row g-9 mb-7">
                <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Nama Promo</label>
                <Field name="name" class="form-control form-control-solid" placeholder="e.g., Diskon Kemerdekaan" />
                <ErrorMessage name="name" class="fv-help-block" />
                </div>
                <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Kode Promo</label>
                <Field name="code" class="form-control form-control-solid" placeholder="e.g., MERDEKA17" />
                <ErrorMessage name="code" class="fv-help-block" />
                </div>
            </div>

            <div class="row g-9 mb-7">
                <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Tipe Diskon</label>
                <Field as="select" name="type" class="form-select form-select-solid">
                    <option value="" disabled>Pilih Tipe</option>
                    <option value="percentage">Persentase (%)</option>
                    <option value="fixed_amount">Jumlah Tetap (Rp)</option>
                </Field>
                <ErrorMessage name="type" class="fv-help-block" />
                </div>
                <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Nilai Diskon</label>
                <div class="input-group">
                    <span v-if="isFixedAmount" class="input-group-text">Rp</span>
                    <input type="text" v-model="formattedValue" class="form-control form-control-solid" :placeholder="valuePlaceholder" />
                    <span v-if="!isFixedAmount" class="input-group-text">%</span>
                </div>
                <ErrorMessage name="value" class="fv-help-block" />
                </div>
            </div>
            
            <div class="row g-9 mb-7">
                <div class="col-md-6 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Tanggal Mulai (Opsional)</label>
                    <Field type="datetime-local" name="start_date" class="form-control form-control-solid" />
                    <ErrorMessage name="start_date" class="fv-help-block" />
                </div>
                <div class="col-md-6 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Tanggal Berakhir (Opsional)</label>
                    <Field type="datetime-local" name="end_date" class="form-control form-control-solid" />
                    <ErrorMessage name="end_date" class="fv-help-block" />
                </div>
            </div>

            <div class="row g-9 mb-7">
                <div class="col-md-6 fv-row">
                <label class="fs-6 fw-semibold mb-2">Minimum Pembelian (Rp)</label>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" v-model="formattedMinPurchase" class="form-control form-control-solid" placeholder="e.g., 50.000" />
                </div>
                <ErrorMessage name="min_purchase" class="fv-help-block" />
                </div>
                <div class="col-md-6 fv-row">
                <label class="fs-6 fw-semibold mb-2">Status</label>
                <div class="form-check form-switch form-check-solid">
                    <Field class="form-check-input" type="checkbox" name="is_active" :value="true" :unchecked-value="false" />
                    <label class="form-check-label">Aktif</label>
                </div>
                </div>
                <div class="col-md-6 fv-row">
                <label class="fs-6 fw-semibold mb-2">Jenis Promo</label>
                <div class="form-check form-switch form-check-solid">
                    <Field class="form-check-input" type="checkbox" name="is_member_only" :value="true" :unchecked-value="false" />
                    <label class="form-check-label">Khusus Member?</label>
                </div>
                </div>
            </div>

            <div class="modal-footer flex-center">
                <button type="reset" data-bs-dismiss="modal" class="btn btn-light me-3">Batal</button>
                <button type="submit" class="btn btn-primary" :data-kt-indicator="loading ? 'on' : 'off'">
                <span class="indicator-label">Simpan</span>
                <span class="indicator-progress">
                    Harap tunggu...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
                </button>
            </div>
            </VForm>
        </div>
        </div>
    </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import * as Yup from "yup";
import ApiService from '@/core/services/ApiService';
import Swal from 'sweetalert2';
import { Modal } from 'bootstrap';

// BARU: Helper functions untuk format Rupiah
const formatRupiah = (value: number | null | undefined) => {
    if (value === null || value === undefined) return "";
    return new Intl.NumberFormat("id-ID").format(value);
};

const parseRupiah = (value: string) => {
    if (!value) return 0;
    return parseInt(value.replace(/[^0-9]/g, ""), 10) || 0;
};

// ... (fungsi formatDateForInput tidak berubah) ...
const formatDateForInput = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 16);
};


const props = defineProps<{ promo: any | null }>();
const emit = defineEmits(['promo-saved']);

const promoModalRef = ref<HTMLElement | null>(null);
const loading = ref(false);
const formRef = ref<InstanceType<typeof VForm> | null>(null);

const isEditMode = computed(() => !!props.promo);

// BARU: Computed property untuk membantu logika kondisional di template
const isFixedAmount = computed(() => formRef.value?.values.type === 'fixed_amount');
const valuePlaceholder = computed(() => isFixedAmount.value ? 'e.g., 10.000' : 'e.g., 15');

// BARU: Computed properties untuk formatting
const formattedMinPurchase = computed({
    get: () => formatRupiah(formRef.value?.values.min_purchase),
    set: (newValue) => {
    formRef.value?.setFieldValue('min_purchase', parseRupiah(newValue));
    },
});

const formattedValue = computed({
    get: () => {
    const rawValue = formRef.value?.values.value;
    return isFixedAmount.value ? formatRupiah(rawValue) : rawValue;
    },
    set: (newValue) => {
    const parsedValue = isFixedAmount.value ? parseRupiah(newValue) : Number(newValue) || 0;
    formRef.value?.setFieldValue('value', parsedValue);
    },
});

watch(() => props.promo, (newPromo) => {
    if (formRef.value) {
        if (newPromo) {
            formRef.value.resetForm({
                values: {
                    ...newPromo,
                    is_active: !!newPromo.is_active,
                    is_member_only: !!newPromo.is_member_only,
                    start_date: formatDateForInput(newPromo.start_date),
                    end_date: formatDateForInput(newPromo.end_date),
                }
            });
        } else {
            formRef.value.resetForm({
                values: {
                    name: "", code: "", type: "", value: undefined,
                    min_purchase: undefined, start_date: null, end_date: null,
                    is_active: true, is_member_only: false,
                }
            });
        }
    }
});

// BARU: Watcher untuk mereset 'value' saat tipe diskon berubah
watch(() => formRef.value?.values.type, (newType, oldType) => {
    if (newType !== oldType) {
        formRef.value?.setFieldValue('value', undefined);
    }
});


const validationSchema = Yup.object().shape({
    name: Yup.string().required().label("Nama Promo"),
    code: Yup.string().required().label("Kode Promo"),
    type: Yup.string().required().label("Tipe Diskon"),
    value: Yup.number().required().min(0).label("Nilai Diskon"),
    min_purchase: Yup.number().min(0).nullable().label("Minimum Pembelian"),
    start_date: Yup.date().nullable().label("Tanggal Mulai"),
    end_date: Yup.date().nullable().min(Yup.ref('start_date'), "Tanggal berakhir harus setelah tanggal mulai").label("Tanggal Berakhir"),
    is_active: Yup.boolean(),
    is_member_only: Yup.boolean(),
});

const submit = (values, { setErrors }) => {
    loading.value = true;
    const payload = { 
        ...values, 
        is_active: !!values.is_active ? 1 : 0,
        is_member_only: !!values.is_member_only ? 1 : 0 
    };
    const promoId = props.promo?.id;
    const request = isEditMode.value
        ? ApiService.put(`/master/promos/${promoId}`, payload)
        : ApiService.post("/master/promos", payload);
    request
        .then(() => {
            Swal.fire("Berhasil!", `Promo berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}.`, "success");
            emit('promo-saved');
            const modalInstance = Modal.getInstance(promoModalRef.value!);
            if (modalInstance) {
                modalInstance.hide();
            }
        })
        .catch(({ response }) => {
            if (response?.data?.errors) {
                setErrors(response.data.errors);
            } else {
                Swal.fire("Error!", "Terjadi kesalahan.", "error");
            }
        })
        .finally(() => {
            loading.value = false;
        });
};
</script>