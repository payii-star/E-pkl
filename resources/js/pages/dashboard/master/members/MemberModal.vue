<template>
    <div class="modal fade" id="kt_modal_member" ref="memberModalRef" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
        <div class="modal-header">
            <h2 class="fw-bold">{{ isEditMode ? 'Edit' : 'Tambah' }} Member</h2>
            <div data-bs-dismiss="modal" class="btn btn-icon btn-sm btn-active-icon-primary">
            <KTIcon icon-name="cross" icon-class="fs-1" />
            </div>
        </div>
        <div class="modal-body py-10 px-lg-17">
            <VForm
                ref="formRef"
                class="form"
                @submit="submit"
                :validation-schema="validationSchema"
                v-slot="{ resetForm }"
            >
            <div class="row g-9 mb-7">
                <div class="col-12 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label>
                <Field name="name" class="form-control form-control-solid" placeholder="e.g., Budi Santoso" />
                <ErrorMessage name="name" class="fv-help-block" />
                </div>
            </div>
            <div class="row g-9 mb-7">
                <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">No. Telepon</label>
                <Field name="phone_number" class="form-control form-control-solid" placeholder="e.g., 08123456789" />
                <ErrorMessage name="phone_number" class="fv-help-block" />
                </div>
                <div class="col-md-6 fv-row">
                <label class="fs-6 fw-semibold mb-2">Email (Opsional)</label>
                <Field name="email" type="email" class="form-control form-control-solid" placeholder="e.g., budi@example.com" />
                <ErrorMessage name="email" class="fv-help-block" />
                </div>
            </div>

            <div class="row g-9 mb-7">
                <div class="col-12 fv-row">
                    <label class="fs-6 fw-semibold mb-2">Alamat (Opsional)</label>
                    <Field as="textarea" name="address" class="form-control form-control-solid" rows="3" placeholder="Masukkan alamat lengkap..."/>
                    <ErrorMessage name="address" class="fv-help-block" />
                </div>
            </div>

            <div class="modal-footer flex-center">
                <button type="button" @click="resetForm()" data-bs-dismiss="modal" class="btn btn-light me-3">Batal</button>
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

const props = defineProps<{ member: any | null }>();
const emit = defineEmits(['member-saved']);

const memberModalRef = ref<HTMLElement | null>(null);
const loading = ref(false);
const isEditMode = computed(() => !!props.member);
const formRef = ref<InstanceType<typeof VForm> | null>(null);

watch(() => props.member, (newMember) => {
    if (formRef.value) {
        if (newMember) {
            formRef.value.resetForm({
                values: { ...newMember }
            });
        } else {
            // PERBARUI: Tambahkan 'address' di nilai default
            formRef.value.resetForm({
                values: {
                    name: '',
                    phone_number: '',
                    email: '',
                    address: '', // <-- TAMBAHKAN
                }
            });
        }
    }
});

const validationSchema = Yup.object().shape({
    name: Yup.string().required().label("Nama"),
    phone_number: Yup.string().required().label("No. Telepon"),
    email: Yup.string().email().nullable().label("Email"),
    address: Yup.string().nullable().label("Alamat"), // <-- TAMBAHKAN
});

const submit = (values, { setErrors }) => {
    loading.value = true;
    const memberId = props.member?.id;

    const request = isEditMode.value
        ? ApiService.put(`/master/members/${memberId}`, values)
        : ApiService.post("/master/members", values);

    request
        .then(() => {
            Swal.fire("Berhasil!", `Member berhasil ${isEditMode.value ? 'diperbarui' : 'ditambahkan'}.`, "success");
            emit('member-saved');
            
            const modalInstance = Modal.getInstance(memberModalRef.value!);
            if (modalInstance) {
                modalInstance.hide();
            }
        })
        .catch(({ response }) => {
            if (response?.data?.errors) {
                setErrors(response.data.errors);
            } else {
                Swal.fire("Error", "Terjadi kesalahan.", "error");
            }
        })
        .finally(() => {
            loading.value = false;
        });
};
</script>