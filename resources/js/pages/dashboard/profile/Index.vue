        <script setup lang="ts">
        import { getAssetPath } from "@/core/helpers/assets";
        import { ref, onMounted } from "vue";
        import { ErrorMessage, Field, Form as VForm } from "vee-validate";
        import Swal from "sweetalert2/dist/sweetalert2.js";
        import * as Yup from "yup";
        import { useAuthStore } from "@/stores/auth";
        import axios from "@/libs/axios";

        // --- STATE MANAGEMENT ---
        const authStore = useAuthStore();
        const emailFormDisplay = ref(false);
        const passwordFormDisplay = ref(false);
        const newPhoto = ref<File | null>(null);

        const profileDetails = ref({
        photo: authStore.user.photo || getAssetPath("media/avatars/blank.png"),
        name: authStore.user.name || "",
        email: authStore.user.email || "",
        phone: authStore.user.phone || "",
        });

        const submitButton1 = ref<HTMLElement | null>(null);
        const updateEmailButton = ref<HTMLElement | null>(null);
        const updatePasswordButton = ref<HTMLElement | null>(null);

        // --- SKEMA VALIDASI (YUP) ---
        const profileDetailsValidator = Yup.object().shape({
        name: Yup.string().required().label("Your name"),
        phone: Yup.string().required().label("Phone number"),
        });

        const changeEmailValidator = Yup.object().shape({
        emailaddress: Yup.string().required().email().label("Email"),
        confirmemailpassword: Yup.string().required().label("Password"),
        });

        const changePasswordValidator = Yup.object().shape({
        currentpassword: Yup.string().required().label("Current password"),
        newpassword: Yup.string().min(8).required().label("New password"),
        confirmpassword: Yup.string()
            .required()
            .oneOf([Yup.ref("newpassword")], "Passwords must match")
            .label("Password Confirmation"),
        });

        // --- FUNCTIONS ---
        onMounted(() => {
        profileDetails.value = {
            photo: authStore.user.photo || getAssetPath("media/avatars/blank.png"),
            name: authStore.user.name || "",
            email: authStore.user.email || "",
            phone: authStore.user.phone || "",
        };
        });

        const onFileChange = (event: Event) => {
        const target = event.target as HTMLInputElement;
        if (target.files && target.files[0]) {
            const file = target.files[0];
            newPhoto.value = file;
            const reader = new FileReader();
            reader.onload = (e) => {
            if (e.target && typeof e.target.result === 'string') {
                profileDetails.value.photo = e.target.result;
            }
            };
            reader.readAsDataURL(file);
        }
        };

        const saveChanges1 = () => {
    console.log("1. Fungsi saveChanges1 dimulai.");

    if (submitButton1.value) {
        console.log("2. Tombol submit ditemukan, loading indicator diaktifkan.");
        submitButton1.value.setAttribute("data-kt-indicator", "on");

        const formData = new FormData();
        formData.append("name", profileDetails.value.name);
        formData.append("phone", profileDetails.value.phone);

        if (newPhoto.value) {
        formData.append("photo", newPhoto.value);
        }

        console.log("3. FormData berhasil dibuat. Siap mengirim ke backend.");
        // Untuk melihat isi FormData, uncomment baris di bawah ini
        // for (let [key, value] of formData.entries()) {
        //   console.log(`${key}:`, value);
        // }

        axios.post("/profile", formData, {
        headers: { "Content-Type": "multipart/form-data" },
        })
        .then(response => {
        console.log("4. SUKSES: Respons diterima dari backend.", response.data);
        Swal.fire({
            text: "Profil berhasil diperbarui!",
            icon: "success",
            buttonsStyling: false,
            confirmButtonText: "Ok",
            customClass: { confirmButton: "btn btn-light-primary" },
        });
        authStore.setAuth(response.data);
        })
        .catch(error => {
        // Jika ada error, kita akan melihatnya di sini
        console.error("5. GAGAL: Terjadi error saat request.", error.response); 
        Swal.fire({
            text: error.response?.data?.message || "Terjadi kesalahan.",
            icon: "error",
            buttonsStyling: false,
            confirmButtonText: "Ok",
            customClass: { confirmButton: "btn btn-light-danger" },
        });
        })
        .finally(() => {
        console.log("6. FINALLY: Proses selesai, indicator dimatikan.");
        submitButton1.value?.removeAttribute("data-kt-indicator");
        });

    } else {
        console.error("ERROR: Ref `submitButton1` tidak ditemukan di template!");
    }
    };

        const removeImage = () => {
            newPhoto.value = null;
            profileDetails.value.photo = getAssetPath("media/avatars/blank.png");
        };

        const updateEmail = (values, { setFieldError, setValues }) => {
        if (updateEmailButton.value) {
            updateEmailButton.value.setAttribute("data-kt-indicator", "on");

            axios.post("/profile/change-email", values)
            .then(response => {
                Swal.fire({
                text: "Email berhasil diubah!",
                icon: "success",
                confirmButtonText: "Ok",
                buttonsStyling: false,
                customClass: {
                    confirmButton: "btn btn-light-primary",
                },
                });
                authStore.setAuth(response.data.user);
                emailFormDisplay.value = false;
                setValues({
                    emailaddress: '',
                    confirmemailpassword: '',
                });
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    setFieldError('confirmemailpassword', error.response.data.message);
                } else {
                    Swal.fire({ text: "Gagal mengubah email.", icon: "error", buttonsStyling: false, customClass: { confirmButton: "btn btn-light-danger" } });
                }
            })
            .finally(() => {
                updateEmailButton.value?.removeAttribute("data-kt-indicator");
            });
        }
        };

        const updatePassword = (values, { setFieldError, setValues }) => {
        if (updatePasswordButton.value) {
            updatePasswordButton.value.setAttribute("data-kt-indicator", "on");

            axios.post("/profile/change-password", values)
            .then(() => {
                // PERBAIKAN DI SINI
                Swal.fire({
                text: "Password berhasil diubah!",
                icon: "success",
                confirmButtonText: "Ok",
                buttonsStyling: false,
                customClass: {
                    confirmButton: "btn btn-light-primary",
                },
                });
                passwordFormDisplay.value = false;
                // Kosongkan field setelah berhasil
                setValues({
                    currentpassword: '',
                    newpassword: '',
                    confirmpassword: '',
                });
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    setFieldError('currentpassword', error.response.data.message);
                } else {
                    Swal.fire({ text: "Gagal mengubah password.", icon: "error", buttonsStyling: false, customClass: { confirmButton: "btn btn-light-danger" } });
                }
            })
            .finally(() => {
                updatePasswordButton.value?.removeAttribute("data-kt-indicator");
            });
        }
        };
        </script>

        <template>
        <div class="card mb-5 mb-xl-10">
            <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_profile_details" aria-expanded="true" aria-controls="kt_account_profile_details">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">Profile Details</h3>
            </div>
            </div>
            <div id="kt_account_profile_details" class="collapse show">
            <VForm id="kt_account_profile_details_form" class="form" novalidate @submit="saveChanges1()" :validation-schema="profileDetailsValidator">
                <div class="card-body border-top p-9">
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label fw-semibold fs-6">Photo</label>
                    <div class="col-lg-8">
                    <div class="image-input image-input-outline" data-kt-image-input="true" :style="{ backgroundImage: `url(${getAssetPath('/media/avatars/blank.png')})` }">
                        <div class="image-input-wrapper w-125px h-125px" :style="`background-image: url(${profileDetails.photo})`"></div>
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change Photo">
                        <i class="bi bi-pencil-fill fs-7"></i>
                        <input type="file" name="avatar" accept=".png, .jpg, .jpeg" @change="onFileChange"/>
                        <input type="hidden" name="avatar_remove" />
                        </label>
                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" @click="removeImage()" title="Remove avatar">
                        <i class="bi bi-x fs-2"></i>
                        </span>
                    </div>
                    <div class="form-text">Allowed file types: png, jpg, jpeg.</div>
                    </div>
                </div>
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label required fw-semibold fs-6">Name</label>
                    <div class="col-lg-8 fv-row">
                    <Field type="text" name="name" class="form-control form-control-lg form-control-solid" placeholder="Your name" v-model="profileDetails.name" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="name" /></div>
                    </div>
                </div>
                <div class="row mb-6">
                    <label class="col-lg-4 col-form-label fw-semibold fs-6"><span class="required">Contact Phone</span></label>
                    <div class="col-lg-8 fv-row">
                    <Field type="tel" name="phone" class="form-control form-control-lg form-control-solid" placeholder="Phone number" v-model="profileDetails.phone" />
                    <div class="fv-plugins-message-container"><ErrorMessage name="phone" /></div>
                    </div>
                </div>
                </div>
                <div class="card-footer d-flex justify-content-end py-6 px-9">
                <button type="reset" class="btn btn-light btn-active-light-primary me-2">Discard</button>
                <button type="submit" id="kt_account_profile_details_submit" ref="submitButton1" class="btn btn-primary">
                    <span class="indicator-label"> Save Changes </span>
                    <span class="indicator-progress"> Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
                </div>
            </VForm>
            </div>
        </div>

        <div class="card mb-5 mb-xl-10">
            <div class="card-header border-0 cursor-pointer" role="button" data-bs-toggle="collapse" data-bs-target="#kt_account_signin_method">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">Sign-in Method</h3>
                </div>
            </div>
            <div id="kt_account_signin_method" class="collapse show">
                <div class="card-body border-top p-9">
                    <div class="d-flex flex-wrap align-items-center mb-8">
                        <div id="kt_signin_email" :class="{ 'd-none': emailFormDisplay }">
                        <div class="fs-4 fw-bolder mb-1">Email Address</div>
                        <div class="fs-6 fw-semibold text-gray-600">{{ profileDetails.email }}</div>
                        </div>
                        <div id="kt_signin_email_edit" class="flex-row-fluid" :class="{ 'd-none': !emailFormDisplay }">
                        <VForm id="kt_signin_change_email" class="form" novalidate @submit="updateEmail" :validation-schema="changeEmailValidator">
                            <div class="row mb-6">
                            <div class="col-lg-6 mb-4 mb-lg-0">
                                <div class="fv-row mb-0">
                                <label for="emailaddress" class="form-label fs-6 fw-bold mb-3">Enter New Email Address</label>
                                <Field type="email" class="form-control form-control-lg form-control-solid" id="emailaddress" placeholder="Email Address" name="emailaddress" />
                                <ErrorMessage name="emailaddress" class="fv-plugins-message-container invalid-feedback"/>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="fv-row mb-0">
                                <label for="confirmemailpassword" class="form-label fs-6 fw-bold mb-3">Confirm Password</label>
                                <Field type="password" class="form-control form-control-lg form-control-solid" name="confirmemailpassword" id="confirmemailpassword" />
                                <ErrorMessage name="confirmemailpassword" class="fv-plugins-message-container invalid-feedback"/>
                                </div>
                            </div>
                            </div>
                            <div class="d-flex">
                            <button id="kt_signin_submit" type="submit" ref="updateEmailButton" class="btn btn-primary me-2 px-6">
                                <span class="indicator-label">Update Email</span>
                                <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                            <button id="kt_signin_cancel" type="button" class="btn btn-color-gray-500 btn-active-light-primary px-6" @click="emailFormDisplay = false">Cancel</button>
                            </div>
                        </VForm>
                        </div>
                        <div id="kt_signin_email_button" class="ms-auto" :class="{ 'd-none': emailFormDisplay }">
                        <button class="btn btn-light fw-bolder px-6" @click="emailFormDisplay = true">Change Email</button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        <div id="kt_signin_password" :class="{ 'd-none': passwordFormDisplay }">
                        <div class="fs-4 fw-bolder mb-1">Password</div>
                        <div class="fs-6 fw-semibold text-gray-600">************</div>
                        </div>
                        <div id="kt_signin_password_edit" class="flex-row-fluid" :class="{ 'd-none': !passwordFormDisplay }">
                        <VForm id="kt_signin_change_password" class="form" novalidate @submit="updatePassword" :validation-schema="changePasswordValidator">
                            <div class="row mb-6">
                            <div class="col-lg-4">
                                <div class="fv-row mb-0">
                                <label for="currentpassword" class="form-label fs-6 fw-bold mb-3">Current Password</label>
                                <Field type="password" class="form-control form-control-lg form-control-solid" name="currentpassword" id="currentpassword" />
                                <ErrorMessage name="currentpassword" class="fv-plugins-message-container invalid-feedback"/>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="fv-row mb-0">
                                <label for="newpassword" class="form-label fs-6 fw-bold mb-3">New Password</label>
                                <Field type="password" class="form-control form-control-lg form-control-solid" name="newpassword" id="newpassword" />
                                <ErrorMessage name="newpassword" class="fv-plugins-message-container invalid-feedback"/>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="fv-row mb-0">
                                <label for="confirmpassword" class="form-label fs-6 fw-bold mb-3">Confirm New Password</label>
                                <Field type="password" class="form-control form-control-lg form-control-solid" name="confirmpassword" id="confirmpassword" />
                                <ErrorMessage name="confirmpassword" class="fv-plugins-message-container invalid-feedback"/>
                                </div>
                            </div>
                            </div>
                            <div class="d-flex">
                            <button id="kt_password_submit" type="submit" ref="updatePasswordButton" class="btn btn-primary me-2 px-6">
                                <span class="indicator-label">Update Password</span>
                                <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                            <button type="button" @click="passwordFormDisplay = false" class="btn btn-color-gray-500 btn-active-light-primary px-6">Cancel</button>
                            </div>
                        </VForm>
                        </div>
                        <div id="kt_signin_password_button" class="ms-auto" :class="{ 'd-none': passwordFormDisplay }">
                        <button @click="passwordFormDisplay = true" class="btn btn-light fw-bolder">Reset Password</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </template>