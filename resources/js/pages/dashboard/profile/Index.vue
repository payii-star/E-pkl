<script setup lang="ts">
import { getAssetPath } from "@/core/helpers/assets";
import { ref, onMounted, computed } from "vue";
import { ErrorMessage, Field, Form as VForm } from "vee-validate";
import Swal from "sweetalert2/dist/sweetalert2.js";
import * as Yup from "yup";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import axios from "@/libs/axios";

// --- STATE MANAGEMENT ---
const authStore = useAuthStore();
const router = useRouter();
const emailFormDisplay = ref(false);
const passwordFormDisplay = ref(false);
const newPhoto = ref<File | null>(null);
const isEditing = ref(false);

const profileDetails = ref({
photo: authStore.user.photo || getAssetPath("media/avatars/blank.png"),
name: authStore.user.name || "",
email: authStore.user.email || "",
phone: authStore.user.phone || "",
});

const submitButton1 = ref<HTMLElement | null>(null);

const blankAvatarPath = getAssetPath("media/avatars/blank.png");

const hasRealPhoto = computed(() => {
    return !!profileDetails.value.photo && profileDetails.value.photo !== blankAvatarPath;
});

const initials = computed(() => {
    const name = profileDetails.value.name?.trim();
    if (!name) return "?";
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
});
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

// Balikin semua perubahan yang belum disimpan ke data asli dari authStore,
// lalu keluar dari mode edit.
const discardChanges = () => {
    profileDetails.value = {
        photo: authStore.user.photo || getAssetPath("media/avatars/blank.png"),
        name: authStore.user.name || "",
        email: authStore.user.email || "",
        phone: authStore.user.phone || "",
    };
    newPhoto.value = null;
    isEditing.value = false;
};

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
    newPhoto.value = null;
    isEditing.value = false;
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

const removingPhoto = ref(false);
const showLightbox = ref(false);

const removeImage = () => {
    if (!hasRealPhoto.value || removingPhoto.value) return;

    Swal.fire({
        text: "Hapus foto profil sekarang?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus",
        cancelButtonText: "Batal",
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-light-danger",
            cancelButton: "btn btn-light",
        },
    }).then((result) => {
        if (!result.isConfirmed) return;

        removingPhoto.value = true;

        const formData = new FormData();
        formData.append("name", profileDetails.value.name);
        formData.append("phone", profileDetails.value.phone);
        formData.append("remove_photo", "1");

        axios.post("/profile", formData, {
            headers: { "Content-Type": "multipart/form-data" },
        })
            .then((response) => {
                newPhoto.value = null;
                profileDetails.value.photo = "";
                authStore.setAuth(response.data);
                Swal.fire({
                    text: "Foto profil berhasil dihapus.",
                    icon: "success",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: { confirmButton: "btn btn-light-primary" },
                });
            })
            .catch((error) => {
                Swal.fire({
                    text: error.response?.data?.message || "Gagal menghapus foto.",
                    icon: "error",
                    buttonsStyling: false,
                    customClass: { confirmButton: "btn btn-light-danger" },
                });
            })
            .finally(() => {
                removingPhoto.value = false;
            });
    });
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

// --- HAPUS AKUN ---
const deletingAccount = ref(false);

const deleteAccount = () => {
    Swal.fire({
        title: "Hapus Akun?",
        html: "Tindakan ini <b>tidak bisa dibatalkan</b>. Semua data profil, foto, dan riwayat kamu akan hilang permanen.<br/><br/>Masukkan password untuk konfirmasi:",
        icon: "warning",
        input: "password",
        inputPlaceholder: "Password kamu",
        inputAttributes: {
            autocapitalize: "off",
            autocomplete: "current-password",
        },
        showCancelButton: true,
        confirmButtonText: "Ya, Hapus Akun Saya",
        cancelButtonText: "Batal",
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            confirmButton: "btn btn-danger",
            cancelButton: "btn btn-light",
        },
        preConfirm: (password) => {
            if (!password) {
                Swal.showValidationMessage("Password wajib diisi.");
            }
            return password;
        },
    }).then((result) => {
        if (!result.isConfirmed || !result.value) return;

        deletingAccount.value = true;

        axios
            .delete("/profile", { data: { password: result.value } })
            .then(async () => {
                await Swal.fire({
                    text: "Akun kamu berhasil dihapus.",
                    icon: "success",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: { confirmButton: "btn btn-light-primary" },
                });
                await authStore.logout();
                router.push({ name: "sign-in" });
            })
            .catch((error) => {
                Swal.fire({
                    text: error.response?.data?.message || "Gagal menghapus akun. Cek kembali password kamu.",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "Ok",
                    customClass: { confirmButton: "btn btn-light-danger" },
                });
            })
            .finally(() => {
                deletingAccount.value = false;
            });
    });
};
</script>

<template>
<div class="card mb-5 mb-xl-10">
    <div class="card-header border-0 pt-6">
        <div class="card-title m-0">
            <h3 class="fw-bold m-0">Profile Details</h3>
        </div>
        <div class="card-toolbar" v-if="!isEditing">
            <button type="button" class="btn btn-sm btn-light-primary" @click="isEditing = true">
                Edit Profil
            </button>
        </div>
    </div>

    <!--begin::Mode Lihat (default, read-only)-->
    <div v-if="!isEditing" class="card-body pt-0">
        <div class="d-flex align-items-center gap-6 mb-9 pb-9 border-bottom">
            <div class="profile-avatar">
                <div
                    v-if="hasRealPhoto"
                    class="profile-avatar__image profile-avatar__image--clickable"
                    :style="`background-image: url(${profileDetails.photo})`"
                    title="Lihat foto"
                    @click="showLightbox = true"
                ></div>
                <div v-else class="profile-avatar__initials">
                    {{ initials }}
                </div>
            </div>
            <div>
                <div class="fw-bold fs-5">{{ profileDetails.name || '-' }}</div>
                <div class="text-gray-500 fs-7">{{ profileDetails.email }}</div>
            </div>
        </div>

        <div class="mb-6">
            <label class="form-label fw-semibold text-gray-500 mb-1">Nama</label>
            <div class="fs-6">{{ profileDetails.name || '-' }}</div>
        </div>

        <div class="mb-2">
            <label class="form-label fw-semibold text-gray-500 mb-1">Nomor Telepon</label>
            <div class="fs-6">{{ profileDetails.phone || '-' }}</div>
        </div>
    </div>
    <!--end::Mode Lihat-->

    <!--begin::Mode Edit-->
    <VForm v-else id="kt_account_profile_details_form" class="form" novalidate @submit="saveChanges1()" :validation-schema="profileDetailsValidator">
        <div class="card-body pt-0">
            <!--begin::Photo-->
            <div class="d-flex align-items-center gap-6 mb-9 pb-9 border-bottom">
                <div class="profile-avatar">
                    <div
                        v-if="hasRealPhoto"
                        class="profile-avatar__image profile-avatar__image--clickable"
                        :style="`background-image: url(${profileDetails.photo})`"
                        title="Lihat foto"
                        @click="showLightbox = true"
                    ></div>
                    <div v-else class="profile-avatar__initials">
                        {{ initials }}
                    </div>

                    <label class="profile-avatar__edit" title="Ganti foto">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                            <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input type="file" name="avatar" accept=".png, .jpg, .jpeg" class="d-none" @change="onFileChange" />
                        <input type="hidden" name="avatar_remove" />
                    </label>
                </div>

                <div>
                    <button
                        v-if="hasRealPhoto"
                        type="button"
                        class="btn btn-sm btn-light-danger"
                        :disabled="removingPhoto"
                        @click="removeImage()"
                    >
                        {{ removingPhoto ? 'Menghapus...' : 'Hapus Foto' }}
                    </button>
                    <div class="form-text mt-2">Format yang didukung: PNG, JPG, JPEG.</div>
                </div>
            </div>
            <!--end::Photo-->

            <!--begin::Name-->
            <div class="mb-6">
                <label class="form-label required fw-semibold">Nama</label>
                <Field
                    type="text"
                    name="name"
                    class="form-control form-control-solid"
                    placeholder="Nama kamu"
                    v-model="profileDetails.name"
                />
                <div class="text-danger fs-8 mt-1"><ErrorMessage name="name" /></div>
            </div>
            <!--end::Name-->

            <!--begin::Phone-->
            <div class="mb-2">
                <label class="form-label required fw-semibold">Nomor Telepon</label>
                <Field
                    type="tel"
                    name="phone"
                    class="form-control form-control-solid"
                    placeholder="Nomor telepon"
                    v-model="profileDetails.phone"
                />
                <div class="text-danger fs-8 mt-1"><ErrorMessage name="phone" /></div>
            </div>
            <!--end::Phone-->
        </div>

        <div class="card-footer d-flex justify-content-end gap-2 py-6">
            <button type="button" class="btn btn-light" @click="discardChanges()">Discard</button>
            <button type="submit" id="kt_account_profile_details_submit" ref="submitButton1" class="btn btn-primary">
                <span class="indicator-label">Save Changes</span>
                <span class="indicator-progress">Please wait...
                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                </span>
            </button>
        </div>
    </VForm>
    <!--end::Mode Edit-->
</div>

<!--begin::Photo Lightbox-->
<div
    v-if="showLightbox && hasRealPhoto"
    class="lightbox-backdrop"
    @click.self="showLightbox = false"
>
    <button class="lightbox-close" @click="showLightbox = false" title="Tutup">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>
    <img :src="profileDetails.photo" alt="Foto profil" class="lightbox-image" />
</div>
<!--end::Photo Lightbox-->

<div class="card mb-5 mb-xl-10">
    <div class="card-header border-0 pt-6">
        <div class="card-title m-0">
            <h3 class="fw-bold m-0">Sign-in Method</h3>
        </div>
    </div>

    <div class="card-body pt-0">
        <!--begin::Email-->
        <div class="signin-row">
            <div v-if="!emailFormDisplay" class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <div class="fw-bold fs-6">Email</div>
                    <div class="text-gray-500 fs-7">{{ profileDetails.email }}</div>
                </div>
                <button class="btn btn-sm btn-light-primary" @click="emailFormDisplay = true">
                    Ganti Email
                </button>
            </div>

            <VForm v-else class="form w-100" novalidate @submit="updateEmail" :validation-schema="changeEmailValidator">
                <div class="fw-bold fs-6 mb-4">Ganti Email</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label for="emailaddress" class="form-label fs-7 mb-1">Email Baru</label>
                        <Field type="email" class="form-control form-control-solid" id="emailaddress" placeholder="Email baru" name="emailaddress" />
                        <div class="text-danger fs-8 mt-1"><ErrorMessage name="emailaddress" /></div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirmemailpassword" class="form-label fs-7 mb-1">Konfirmasi Password</label>
                        <Field type="password" class="form-control form-control-solid" name="confirmemailpassword" id="confirmemailpassword" />
                        <div class="text-danger fs-8 mt-1"><ErrorMessage name="confirmemailpassword" /></div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button id="kt_signin_submit" type="submit" ref="updateEmailButton" class="btn btn-primary btn-sm">
                        <span class="indicator-label">Update Email</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    <button id="kt_signin_cancel" type="button" class="btn btn-light btn-sm" @click="emailFormDisplay = false">Batal</button>
                </div>
            </VForm>
        </div>
        <!--end::Email-->

        <div class="separator my-6"></div>

        <!--begin::Password-->
        <div class="signin-row">
            <div v-if="!passwordFormDisplay" class="d-flex align-items-center justify-content-between w-100">
                <div>
                    <div class="fw-bold fs-6">Password</div>
                    <div class="text-gray-500 fs-7">••••••••••••</div>
                </div>
                <button class="btn btn-sm btn-light-primary" @click="passwordFormDisplay = true">
                    Reset Password
                </button>
            </div>

            <VForm v-else class="form w-100" novalidate @submit="updatePassword" :validation-schema="changePasswordValidator">
                <div class="fw-bold fs-6 mb-4">Ganti Password</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label for="currentpassword" class="form-label fs-7 mb-1">Password Saat Ini</label>
                        <Field type="password" class="form-control form-control-solid" name="currentpassword" id="currentpassword" />
                        <div class="text-danger fs-8 mt-1"><ErrorMessage name="currentpassword" /></div>
                    </div>
                    <div class="col-md-4">
                        <label for="newpassword" class="form-label fs-7 mb-1">Password Baru</label>
                        <Field type="password" class="form-control form-control-solid" name="newpassword" id="newpassword" />
                        <div class="text-danger fs-8 mt-1"><ErrorMessage name="newpassword" /></div>
                    </div>
                    <div class="col-md-4">
                        <label for="confirmpassword" class="form-label fs-7 mb-1">Konfirmasi Password Baru</label>
                        <Field type="password" class="form-control form-control-solid" name="confirmpassword" id="confirmpassword" />
                        <div class="text-danger fs-8 mt-1"><ErrorMessage name="confirmpassword" /></div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button id="kt_password_submit" type="submit" ref="updatePasswordButton" class="btn btn-primary btn-sm">
                        <span class="indicator-label">Update Password</span>
                        <span class="indicator-progress">Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                    <button type="button" @click="passwordFormDisplay = false" class="btn btn-light btn-sm">Batal</button>
                </div>
            </VForm>
        </div>
        <!--end::Password-->
    </div>
</div>

<!--begin::Danger Zone-->
<div class="card mb-5 mb-xl-10 border border-danger border-opacity-25">
    <div class="card-header border-0 pt-6">
        <div class="card-title m-0">
            <h3 class="fw-bold m-0 text-danger">Hapus Akun</h3>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="text-gray-500 fs-7">
                    Setelah dihapus, akun dan semua data kamu tidak bisa dikembalikan.
                </div>
            </div>
            <button
                type="button"
                class="btn btn-sm btn-danger"
                :disabled="deletingAccount"
                @click="deleteAccount()"
            >
                {{ deletingAccount ? 'Menghapus...' : 'Hapus Akun' }}
            </button>
        </div>
    </div>
</div>
<!--end::Danger Zone-->
</template>

<style scoped>
.profile-avatar {
    position: relative;
    width: 96px;
    height: 96px;
    flex-shrink: 0;
}
.profile-avatar__image {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    background-size: cover;
    background-position: center;
    border: 1px solid var(--bs-border-color, #2b2b40);
    background-color: var(--bs-gray-100, #1e1e2d);
}
.profile-avatar__image--clickable {
    cursor: pointer;
    transition: filter 0.15s ease;
}
.profile-avatar__image--clickable:hover {
    filter: brightness(0.85);
}
.profile-avatar__initials {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 32px;
    font-weight: 700;
    color: #fff;
    background: var(--bs-primary, #009ef7);
    border: 1px solid var(--bs-border-color, #2b2b40);
    user-select: none;
}
.profile-avatar__edit {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--bs-primary, #009ef7);
    color: #fff;
    display: grid;
    place-items: center;
    cursor: pointer;
    border: 3px solid var(--bs-body-bg, #151521);
    transition: filter 0.15s ease;
}
.profile-avatar__edit:hover {
    filter: brightness(1.1);
}

.signin-row {
    display: flex;
    width: 100%;
}

.lightbox-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1060;
    padding: 24px;
}
.lightbox-image {
    width: min(70vw, 360px);
    height: min(70vw, 360px);
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
.lightbox-close {
    position: absolute;
    top: 20px;
    right: 24px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    display: grid;
    place-items: center;
    cursor: pointer;
    transition: background 0.15s ease;
}
.lightbox-close:hover {
    background: rgba(255, 255, 255, 0.22);
}
</style>