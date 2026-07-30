<template>
    <VForm class="card mb-10" @submit="submit" :validation-schema="formSchema">
        <div class="card-header align-items-center">
            <h2 class="mb-0">Konfigurasi Website</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Nama Aplikasi</label>
                        <Field class="form-control form-control-lg form-control-solid" type="text" name="app"
                            autocomplete="off" v-model="formData.app" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="app" />
                            </div>
                        </div>
                    </div>
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Deskripsi</label>
                        <Field as="textarea" class="form-control form-control-lg form-control-solid" name="description"
                            autocomplete="off" v-model="formData.description" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="description" />
                            </div>
                        </div>
                    </div>
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Alamat</label>
                        <Field class="form-control form-control-lg form-control-solid" type="text" name="alamat"
                            autocomplete="off" v-model="formData.alamat" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="alamat" />
                            </div>
                        </div>
                    </div>
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Telepon</label>
                        <Field class="form-control form-control-lg form-control-solid" type="text" name="telepon"
                            autocomplete="off" v-model="formData.telepon" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="telepon" />
                            </div>
                        </div>
                    </div>
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold fs-6 required">Email</label>
                        <Field class="form-control form-control-lg form-control-solid" type="text" name="email"
                            autocomplete="off" v-model="formData.email" />
                        <div class="fv-plugins-message-container">
                            <div class="fv-help-block">
                                <ErrorMessage name="email" />
                            </div>
                        </div>
                    </div>
                    </div>

                <div class="col-12 d-md-none">
                    <div class="border border-bottom border-gray mt-8 mb-12"></div>
                </div>

                <div class="col-md-6">
                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold">Logo</label> <file-upload v-bind:files="files.logo" :accepted-file-types="fileTypes"
                            v-on:updatefiles="file => files.logo = file"></file-upload> </div>

                    <div class="fv-row mb-8">
                        <label class="form-label fw-bold">Background Login</label> <file-upload v-bind:files="files.bgAuth" :accepted-file-types="fileTypes"
                            v-on:updatefiles="file => files.bgAuth = file"></file-upload> </div>
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

<script lang="ts">
import { block, unblock } from '@/libs/utils';
import { defineComponent, ref } from 'vue'
import * as Yup from 'yup';
import axios from '@/libs/axios';
import { toast } from 'vue3-toastify';
import { useSetting } from '@/services';
import type { Setting } from '@/types';

export default defineComponent({
    props: {
        selected: {
            type: String,
            default: null
        },
    },
    setup() {
        const setting = useSetting()
        const formData = ref<Setting>({ ...setting.data?.value })

        const fileTypes = ref(['image/jpeg', 'image/png', 'image/jpg'])
        const files = ref({
            logo: setting.data?.value?.logo ? [setting.data.value.logo] : [],
            bgAuth: setting.data?.value?.bg_auth ? [setting.data.value.bg_auth] : [],
        })

        // Skema validasi disederhanakan
        const formSchema = Yup.object().shape({
            alamat: Yup.string().required('Alamat wajib diisi'),
            app: Yup.string().required('Nama aplikasi wajib diisi'),
            description: Yup.string().required('Deskripsi wajib diisi'),
            email: Yup.string().email('Format email tidak valid').required('Email wajib diisi'),
            telepon: Yup.string().required('Telepon wajib diisi'),
        })

        return {
            setting,
            formData,
            formSchema,
            fileTypes,
            files
        }
    },
    methods: {
        // Method submit diperbarui total
        submit(values) {
            const data = new FormData();

            // Tambahkan semua data teks dari form yang sudah divalidasi
            for (const key in values) {
                if (values[key]) {
                    data.append(key, values[key]);
                }
            }

            // Cek dan tambahkan 'logo' HANYA jika ada file baru yang dipilih
            if (this.files.logo[0] && this.files.logo[0].file) {
                data.append('logo', this.files.logo[0].file);
            }

            // Cek dan tambahkan 'bg_auth' HANYA jika ada file baru yang dipilih
            if (this.files.bgAuth[0] && this.files.bgAuth[0].file) {
                data.append('bg_auth', this.files.bgAuth[0].file);
            }

            // Kirim data ke server
            block(this.$el)
            axios.post("/setting", data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
                .then((res) => {
                    toast.success(res.data.message)
                    this.setting.refetch()
                })
                .catch(err => {
                    toast.error(err.response.data.message)
                })
                .finally(() => {
                    unblock(this.$el)
                })
        }
    },
    watch: {
        setting: {
            handler(setting) {
                this.formData = setting.data.value

                this.files.logo = setting.data.value.logo ? [setting.data.value.logo] : []
                this.files.bgAuth = setting.data.value.bg_auth ? [setting.data.value.bg_auth] : []
            },
            deep: true
        }
    }
})
</script>