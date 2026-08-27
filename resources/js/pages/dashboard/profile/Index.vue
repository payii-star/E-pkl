<template>
    <div class="profile-page">
        <div class="card shadow-sm">
            <div class="card-body p-8">
                <!-- HEADER -->
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h2 class="fw-bold mb-1">Profile Saya</h2>
                        <p class="text-muted mb-0">
                            Kelola informasi akun kamu
                        </p>
                    </div>

                    <button
                        class="btn btn-primary"
                        @click="openEdit"
                    >
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Profile
                    </button>
                </div>

                <div class="separator my-8"></div>

                <!-- FOTO + NAMA -->
                <div class="d-flex align-items-center gap-5 flex-wrap">
                    <div class="symbol symbol-100px symbol-circle">
                        <img
                            v-if="profile.photo"
                            :src="profile.photo"
                            alt="Profile"
                        />

                        <div
                            v-else
                            class="symbol-label bg-light-primary text-primary fs-1 fw-bold"
                        >
                            {{ initials }}
                        </div>
                    </div>

                    <div>
                        <h3 class="fw-bold mb-1">
                            {{ profile.name || "-" }}
                        </h3>

                        <div class="text-muted mb-1">
                            {{ profile.email || "-" }}
                        </div>

                        <span class="badge badge-light-primary">
                            {{ roleName }}
                        </span>
                    </div>
                </div>

                <div class="separator my-8"></div>

                <!-- DATA PROFILE -->
                <div class="row g-7">
                    <div class="col-md-6">
                        <div class="profile-item">
                            <div class="profile-label">
                                Nama Lengkap
                            </div>

                            <div class="profile-value">
                                {{ profile.name || "-" }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-item">
                            <div class="profile-label">
                                Email
                            </div>

                            <div class="profile-value">
                                {{ profile.email || "-" }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-item">
                            <div class="profile-label">
                                NIS
                            </div>

                            <div class="profile-value">
                                {{ profile.nim_nis || "-" }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="profile-item">
                            <div class="profile-label">
                                Nomor Telepon
                            </div>

                            <div class="profile-value">
                                {{ profile.phone || "-" }}
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="profile-item">
                            <div class="profile-label">
                                Asal Sekolah
                            </div>

                            <div class="profile-value">
                                {{ profile.asal_instansi || "-" }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT -->
        <div
            v-if="showModal"
            class="custom-modal-backdrop"
            @click.self="closeEdit"
        >
            <div class="custom-modal">
                <div class="card">
                    <!-- HEADER MODAL -->
                    <div class="card-header">
                        <div class="card-title">
                            <h3 class="fw-bold">
                                Edit Profile
                            </h3>
                        </div>

                        <button
                            class="btn btn-sm btn-icon btn-light"
                            @click="closeEdit"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="card-body">
                        <form @submit.prevent="saveProfile">

                            <!-- NAMA -->
                            <div class="mb-5">
                                <label class="form-label required">
                                    Nama Lengkap
                                </label>

                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="form-control"
                                    placeholder="Masukkan nama lengkap"
                                    required
                                />
                            </div>

                            <!-- EMAIL -->
                            <div class="mb-5">
                                <label class="form-label">
                                    Email
                                </label>

                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    class="form-control"
                                    disabled
                                />
                            </div>

                            <!-- NIS -->
                            <div class="mb-5">
                                <label class="form-label required">
                                    NIS
                                </label>

                                <input
                                    v-model="editForm.nim_nis"
                                    type="text"
                                    class="form-control"
                                    placeholder="Masukkan NIS"
                                    required
                                />
                            </div>

                            <!-- SEKOLAH -->
                            <div class="mb-5">
                                <label class="form-label required">
                                    Asal Sekolah
                                </label>

                                <input
                                    v-model="editForm.asal_instansi"
                                    type="text"
                                    class="form-control"
                                    placeholder="Masukkan asal sekolah"
                                    required
                                />
                            </div>

                            <!-- TELEPON -->
                            <div class="mb-5">
                                <label class="form-label">
                                    Nomor Telepon
                                </label>

                                <input
                                    v-model="editForm.phone"
                                    type="text"
                                    class="form-control"
                                    placeholder="Masukkan nomor telepon"
                                />
                            </div>

                            <!-- ERROR -->
                            <div
                                v-if="errorMessage"
                                class="alert alert-danger"
                            >
                                {{ errorMessage }}
                            </div>

                            <!-- BUTTON -->
                            <div class="d-flex justify-content-end gap-3">
                                <button
                                    type="button"
                                    class="btn btn-light"
                                    :disabled="loading"
                                    @click="closeEdit"
                                >
                                    Batal
                                </button>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="loading"
                                >
                                    <span
                                        v-if="loading"
                                        class="spinner-border spinner-border-sm me-2"
                                    ></span>

                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import axios from "@/libs/axios";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

/* =========================
   INTERFACE
========================= */

interface ProfileData {
    id?: number;
    name: string;
    email: string;
    phone: string;
    nim_nis: string;
    asal_instansi: string;
    photo?: string | null;
}

/* =========================
   STATE
========================= */

const profile = ref<ProfileData>({
    id: 0,
    name: "",
    email: "",
    phone: "",
    nim_nis: "",
    asal_instansi: "",
    photo: null,
});

const editForm = ref<ProfileData>({
    id: 0,
    name: "",
    email: "",
    phone: "",
    nim_nis: "",
    asal_instansi: "",
    photo: null,
});

const showModal = ref(false);
const loading = ref(false);
const errorMessage = ref("");

/* =========================
   COMPUTED
========================= */

const initials = computed(() => {
    if (!profile.value.name) return "U";

    return profile.value.name
        .split(" ")
        .slice(0, 2)
        .map((item) => item.charAt(0).toUpperCase())
        .join("");
});

const roleName = computed(() => {
    if (!authStore.user?.role?.name) {
        return "User";
    }

    return authStore.user.role.name;
});

/* =========================
   LOAD PROFILE
========================= */

const loadProfile = async () => {
    try {
        const response = await axios.get("/auth/me");

        const user =
            response.data?.data ||
            response.data?.user ||
            response.data;

        profile.value = {
            id: user.id,
            name: user.name || "",
            email: user.email || "",
            phone: user.phone || "",
            nim_nis: user.nim_nis || "",
            asal_instansi: user.asal_instansi || "",
            photo:
                user.profile_photo ||
                user.photo ||
                null,
        };
    } catch (error) {
        console.error("Gagal mengambil profile", error);
    }
};

/* =========================
   EDIT
========================= */

const openEdit = () => {
    errorMessage.value = "";

    editForm.value = {
        id: profile.value.id,
        name: profile.value.name,
        email: profile.value.email,
        phone: profile.value.phone,
        nim_nis: profile.value.nim_nis,
        asal_instansi: profile.value.asal_instansi,
        photo: profile.value.photo,
    };

    showModal.value = true;
};

const closeEdit = () => {
    if (loading.value) return;

    showModal.value = false;
    errorMessage.value = "";
};

/* =========================
   SAVE
========================= */

const saveProfile = async () => {
    errorMessage.value = "";

    if (!editForm.value.name.trim()) {
        errorMessage.value = "Nama wajib diisi";
        return;
    }

    if (!editForm.value.nim_nis.trim()) {
        errorMessage.value = "NIS wajib diisi";
        return;
    }

    if (!editForm.value.asal_instansi.trim()) {
        errorMessage.value = "Asal sekolah wajib diisi";
        return;
    }

    loading.value = true;

    try {
        const formData = new FormData();

        formData.append(
            "name",
            editForm.value.name
        );

        formData.append(
            "phone",
            editForm.value.phone || ""
        );

        formData.append(
            "nim_nis",
            editForm.value.nim_nis
        );

        formData.append(
            "asal_instansi",
            editForm.value.asal_instansi
        );

        formData.append("_method", "PUT");

        const response = await axios.post(
            "/profile",
            formData
        );

        const user =
            response.data?.data ||
            response.data?.user ||
            response.data;

        profile.value = {
            id: user.id,
            name: user.name || editForm.value.name,
            email: user.email || editForm.value.email,
            phone: user.phone || editForm.value.phone,
            nim_nis:
                user.nim_nis ||
                editForm.value.nim_nis,
            asal_instansi:
                user.asal_instansi ||
                editForm.value.asal_instansi,
            photo:
                user.profile_photo ||
                profile.value.photo,
        };

        if (authStore.user) {
            Object.assign(authStore.user, {
                name: profile.value.name,
                phone: profile.value.phone,
                nim_nis: profile.value.nim_nis,
                asal_instansi: profile.value.asal_instansi,
            });
        }

        showModal.value = false;
    } catch (error: any) {
        const errors =
            error?.response?.data?.errors;

        if (errors) {
            const first = Object.values(errors)
                .flat()
                .find(Boolean);

            errorMessage.value =
                String(first);
        } else {
            errorMessage.value =
                error?.response?.data?.message ||
                "Gagal menyimpan profile";
        }
    } finally {
        loading.value = false;
    }
};

/* =========================
   INIT
========================= */

onMounted(() => {
    loadProfile();
});
</script>

<style scoped>
.profile-page {
    width: 100%;
}

.profile-item {
    padding: 18px;
    border: 1px solid #eef0f4;
    border-radius: 12px;
    background: #fff;
    height: 100%;
}

.profile-label {
    font-size: 13px;
    color: #7e8299;
    margin-bottom: 6px;
}

.profile-value {
    font-size: 15px;
    font-weight: 600;
    color: #181c32;
}

.custom-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.custom-modal {
    width: 100%;
    max-width: 600px;
}

.separator {
    border-top: 1px solid #eff2f5;
}

@media (max-width: 576px) {
    .custom-modal-backdrop {
        align-items: flex-start;
        overflow-y: auto;
    }

    .custom-modal {
        margin-top: 30px;
    }
}
</style>