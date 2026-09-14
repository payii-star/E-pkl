<template>
    <div class="w-100 signup">
        <!-- STEP 1: FORM AKUN -->
        <template v-if="step === 'account'">
            <div class="signup__header">
                <h2>Buat Akun Baru</h2>
                <p>
                    Isi data di bawah untuk membuat akun baru.
                </p>
            </div>

            <form
                class="auth-form"
                @submit.prevent="submitRegistration"
            >
                <!-- NAMA LENGKAP -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        Nama Lengkap
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                width="16"
                                height="16"
                            >
                                <circle
                                    cx="12"
                                    cy="8"
                                    r="3.4"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M5 19.2c1.2-3.4 4-5.2 7-5.2s5.8 1.8 7 5.2"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <input
                            v-model="form.name"
                            type="text"
                            class="auth-field__input auth-field__input--icon"
                            required
                            maxlength="255"
                            autocomplete="name"
                            placeholder="Masukkan nama lengkap"
                        />
                    </div>
                </div>

                <!-- EMAIL -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        Email
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                width="16"
                                height="16"
                            >
                                <path
                                    d="M4 6.5h16v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-11Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="m4.5 7 7.5 6 7.5-6"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>

                        <input
                            v-model="form.email"
                            type="email"
                            class="auth-field__input auth-field__input--icon"
                            required
                            maxlength="255"
                            autocomplete="email"
                            placeholder="Masukkan email"
                        />
                    </div>
                </div>

                <!-- NIS -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        NIS
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <i class="bi bi-card-text"></i>
                        </span>

                        <input
                            v-model="form.nim_nis"
                            type="text"
                            class="auth-field__input auth-field__input--icon"
                            required
                            minlength="5"
                            maxlength="18"
                            inputmode="numeric"
                            autocomplete="off"
                            placeholder="Masukkan NIS"
                            @input="sanitizeNis"
                        />
                    </div>

                    <div class="form-text">
                        NIS harus terdiri dari 5–18 angka.
                    </div>
                </div>

                <!-- ASAL SEKOLAH -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        Asal Sekolah
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <i class="bi bi-building"></i>
                        </span>

                        <input
                            v-model="form.asal_instansi"
                            type="text"
                            class="auth-field__input auth-field__input--icon"
                            required
                            maxlength="255"
                            autocomplete="organization"
                            placeholder="Masukkan asal sekolah"
                        />
                    </div>
                </div>

                <!-- NOMOR TELEPON -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        No. Telepon
                        <span class="text-muted">
                            (opsional)
                        </span>
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                width="16"
                                height="16"
                            >
                                <path
                                    d="M6.5 4h2.2l1.3 4-1.9 1.4a11 11 0 0 0 5.5 5.5l1.4-1.9 4 1.3v2.2c0 1-.8 1.8-1.8 1.7A16 16 0 0 1 4.8 5.8C4.7 4.8 5.5 4 6.5 4Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>

                        <input
                            v-model="form.phone"
                            type="tel"
                            class="auth-field__input auth-field__input--icon"
                            maxlength="30"
                            autocomplete="tel"
                            placeholder="Masukkan nomor telepon"
                        />
                    </div>
                </div>

                <!-- PASSWORD -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        Password
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                width="16"
                                height="16"
                            >
                                <rect
                                    x="5"
                                    y="10.5"
                                    width="14"
                                    height="9.5"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M8 10.5V7.5a4 4 0 0 1 8 0v3"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <input
                            v-model="form.password"
                            :type="
                                showPassword
                                    ? 'text'
                                    : 'password'
                            "
                            class="auth-field__input auth-field__input--icon auth-field__input--icon-right"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Minimal 8 karakter"
                        />

                        <button
                            type="button"
                            class="auth-field__toggle"
                            @click="
                                showPassword =
                                    !showPassword
                            "
                        >
                            <i
                                :class="
                                    showPassword
                                        ? 'bi bi-eye'
                                        : 'bi bi-eye-slash'
                                "
                                class="fs-4"
                            ></i>
                        </button>
                    </div>
                </div>

                <!-- KONFIRMASI PASSWORD -->
                <div class="auth-field">
                    <label class="auth-field__label">
                        Konfirmasi Password
                    </label>

                    <div class="auth-field__wrap">
                        <span class="auth-field__icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                width="16"
                                height="16"
                            >
                                <rect
                                    x="5"
                                    y="10.5"
                                    width="14"
                                    height="9.5"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="M8 10.5V7.5a4 4 0 0 1 8 0v3"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>

                        <input
                            v-model="
                                form.password_confirmation
                            "
                            :type="
                                showPasswordConfirm
                                    ? 'text'
                                    : 'password'
                            "
                            class="auth-field__input auth-field__input--icon auth-field__input--icon-right"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            placeholder="Ulangi password"
                        />

                        <button
                            type="button"
                            class="auth-field__toggle"
                            @click="
                                showPasswordConfirm =
                                    !showPasswordConfirm
                            "
                        >
                            <i
                                :class="
                                    showPasswordConfirm
                                        ? 'bi bi-eye'
                                        : 'bi bi-eye-slash'
                                "
                                class="fs-4"
                            ></i>
                        </button>
                    </div>
                </div>

                <!-- ERROR -->
                <div
                    v-if="accountError"
                    class="alert alert-danger py-2 fs-7"
                >
                    {{ accountError }}
                </div>

                <button
                    type="submit"
                    class="auth-submit w-100"
                >
                    Buat Akun
                </button>
            </form>

            <div class="text-center mt-4">
                <router-link
                    to="/sign-in"
                    class="link-primary fw-bold"
                >
                    Sudah punya akun? Masuk
                </router-link>
            </div>
        </template>

        <template v-else>
            <div
                class="signup__header text-center"
            >
                <h2>Semua Siap!</h2>

                <p>
                    Akun kamu sudah dibuat.
                </p>
            </div>

            <button
                class="auth-submit w-100"
                @click="goToDashboard"
            >
                Masuk ke Dashboard
            </button>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import axios from "@/libs/axios";
import { useRouter } from "vue-router";

const router = useRouter();

/*
|--------------------------------------------------------------------------
| STEP
|--------------------------------------------------------------------------
*/

const step = ref<
    "account" | "done"
>("account");

/*
|--------------------------------------------------------------------------
| FORM
|--------------------------------------------------------------------------
*/

const form = ref({
    name: "",
    email: "",
    phone: "",
    nim_nis: "",
    asal_instansi: "",
    password: "",
    password_confirmation: "",
});

const accountError = ref("");

const showPassword = ref(false);

const showPasswordConfirm =
    ref(false);

/*
|--------------------------------------------------------------------------
| SANITIZE NIS
|--------------------------------------------------------------------------
|
| NIS hanya boleh angka.
| Panjang maksimal 18 karakter.
|
*/

function sanitizeNis(
    event: Event
) {
    const target =
        event.target as HTMLInputElement;

    const value =
        target.value
            .replace(/\D/g, "")
            .slice(0, 18);

    form.value.nim_nis = value;
}

/*
|--------------------------------------------------------------------------
| VALIDASI FORM
|--------------------------------------------------------------------------
*/

async function submitRegistration() {
    accountError.value = "";

    const name = form.value.name.trim();
    const email = form.value.email.trim();
    const nis = form.value.nim_nis.trim();
    const school = form.value.asal_instansi.trim();

    if (!name) {
        accountError.value = "Nama lengkap wajib diisi.";
        return;
    }

    if (!email) {
        accountError.value = "Email wajib diisi.";
        return;
    }

    if (!nis) {
        accountError.value = "NIS wajib diisi.";
        return;
    }

    if (!/^\d+$/.test(nis)) {
        accountError.value = "NIS hanya boleh berisi angka.";
        return;
    }

    if (nis.length < 5) {
        accountError.value = "NIS minimal 5 angka.";
        return;
    }

    if (nis.length > 18) {
        accountError.value = "NIS maksimal 18 angka.";
        return;
    }

    if (!school) {
        accountError.value = "Asal sekolah wajib diisi.";
        return;
    }

    if (!form.value.password) {
        accountError.value = "Password wajib diisi.";
        return;
    }

    if (form.value.password.length < 8) {
        accountError.value = "Password minimal 8 karakter.";
        return;
    }

    if (form.value.password !== form.value.password_confirmation) {
        accountError.value = "Konfirmasi password tidak cocok.";
        return;
    }

    try {
        const response = await axios.post("/auth/register", {
            name,
            email,
            phone: form.value.phone.trim() || null,
            nim_nis: nis,
            asal_instansi: school,
            password: form.value.password,
            password_confirmation: form.value.password_confirmation,
        });

        if (response.data?.status !== false) {
            step.value = "done";
        }
    } catch (error: any) {
        const response = error?.response;
        const errors = response?.data?.errors;

        if (errors) {
            const firstError = Object.values(errors).flat().find(Boolean);
            accountError.value = String(firstError || "Data pendaftaran tidak valid.");
        } else {
            accountError.value = response?.data?.message || "Pendaftaran gagal. Silakan coba lagi.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| KEMBALI
|--------------------------------------------------------------------------
*/

function backToAccountStep() {
    step.value = "account";
}

/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

function goToDashboard() {
    router.push({
        name: "dashboard",
    });
}

</script>

<style scoped>
.signup {
    width: 100%;
}

.signup__header {
    margin-bottom: 24px;
}

.signup__header h2 {
    margin-bottom: 6px;
}

.signup__header p {
    margin-bottom: 0;
    color: #8b96a7;
}

.auth-form {
    width: 100%;
}

.auth-field {
    margin-bottom: 18px;
}

.auth-field__label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
}

.auth-field__wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.auth-field__icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    color: #9caed0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.auth-field__input {
    width: 100%;
    min-height: 52px;
    border: 1px solid #dce5f5;
    border-radius: 10px;
    outline: none;
    background: #fff;
    color: #24324a;
    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease;
}

.auth-field__input--icon {
    padding-left: 44px;
}

.auth-field__input--icon-right {
    padding-right: 48px;
}

.auth-field__input:focus {
    border-color: #2f66e8;

    box-shadow:
        0 0 0 3px
        rgba(47, 102, 232, 0.1);
}

.auth-field__toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #8d96a7;
    padding: 4px;
    cursor: pointer;
}

.auth-submit {
    min-height: 50px;
    border: 0;
    border-radius: 10px;
    background: #2f66e8;
    color: #fff;
    font-weight: 600;
    transition: all 0.2s ease;
}

.auth-submit:hover:not(:disabled) {
    filter: brightness(0.95);
}

.auth-submit:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.signup__cam {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: 16px;
    background: #101318;
    aspect-ratio: 4 / 3;
}

.signup__cam video {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transform: scaleX(-1);
}

.signup__cam canvas {
    display: none;
}

.signup__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(
        10,
        15,
        25,
        0.72
    );
    color: #fff;
    text-align: center;
}

.signup__overlay--err {
    color: #ffb4b4;
}

.signup__status {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    font-size: 14px;
    margin-top: 14px;
}

.signup__dot {
    width: 8px;
    height: 8px;
    flex: 0 0 auto;
    border-radius: 50%;
    display: inline-block;
}

.signup__dot--warn {
    background: #f2b84b;
}

.signup__dot--ok {
    background: #3bc47c;
}

.signup__progress {
    height: 6px;
    width: 100%;
    margin-bottom: 18px;
    overflow: hidden;
    border-radius: 999px;
    background: #e6ebf3;
}

.signup__progress-bar {
    height: 100%;
    border-radius: inherit;
    background: #2f66e8;
    transition: width 0.25s ease;
}

.signup__retry {
    width: 100%;
    min-height: 44px;
    border: 1px solid #dce5f5;
    border-radius: 10px;
    background: transparent;
    color: #2f66e8;
    font-weight: 600;
}

.signup__retry:hover {
    background: #f4f7fc;
}

.form-text {
    margin-top: 5px;
    font-size: 12px;
    color: #8b96a7;
}

@media (max-width: 576px) {
    .signup__header h2 {
        font-size: 24px;
    }

    .signup__cam {
        border-radius: 12px;
    }
}
</style>