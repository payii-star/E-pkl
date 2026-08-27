<template>
    <div class="w-100 signup">
        <!-- STEP 1: FORM AKUN -->
        <template v-if="step === 'account'">
            <div class="signup__header">
                <h2>Buat Akun Baru</h2>
                <p>
                    Isi data di bawah, lalu lanjutkan ke
                    pendaftaran wajah.
                </p>
            </div>

            <form
                class="auth-form"
                @submit.prevent="goToFaceStep"
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
                    Lanjut ke Pendaftaran Wajah
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

        <!-- STEP 2: FACE -->
        <template v-else-if="step === 'face'">
            <div class="signup__header">
                <h2>Daftarkan Wajah Kamu</h2>

                <p>
                    Diamkan wajah menghadap kamera.
                    Sistem akan mengambil
                    {{ SAMPLE_TARGET }} sample otomatis.
                </p>
            </div>

            <div
                class="signup__cam"
                :class="`signup__cam--${camStatus}`"
            >
                <video
                    ref="videoEl"
                    autoplay
                    muted
                    playsinline
                ></video>

                <canvas ref="canvasEl"></canvas>

                <div
                    v-if="
                        camStatus ===
                        'loading_models'
                    "
                    class="signup__overlay"
                >
                    <span
                        class="spinner-border spinner-border-sm me-2"
                    ></span>

                    {{
                        modelProgress ||
                        "Menyiapkan kamera..."
                    }}
                </div>

                <div
                    v-else-if="
                        camStatus ===
                        'camera_error'
                    "
                    class="signup__overlay signup__overlay--err"
                >
                    Tidak bisa mengakses kamera.
                    Pastikan izin kamera diaktifkan.
                </div>
            </div>

            <div class="signup__status">
                <template
                    v-if="
                        camStatus ===
                        'no_face'
                    "
                >
                    <span
                        class="signup__dot signup__dot--warn"
                    ></span>

                    Arahkan wajah ke kamera
                </template>

                <template
                    v-else-if="
                        camStatus ===
                        'sampling'
                    "
                >
                    <span
                        class="signup__dot signup__dot--warn"
                    ></span>

                    Mengambil sample wajah...

                    {{ collectedSamples.length }}/{{
                        SAMPLE_TARGET
                    }}
                </template>

                <template
                    v-else-if="
                        camStatus === 'ready'
                    "
                >
                    <span
                        class="signup__dot signup__dot--ok"
                    ></span>

                    {{ collectedSamples.length }}/{{
                        SAMPLE_TARGET
                    }}

                    sample terkumpul,
                    siap didaftarkan
                </template>

                <template
                    v-else-if="
                        camStatus === 'capturing'
                    "
                >
                    <span
                        class="signup__dot signup__dot--warn"
                    ></span>

                    Membuat akun dan
                    menyimpan data wajah...
                </template>

                <template
                    v-else-if="
                        camStatus === 'success'
                    "
                >
                    <span
                        class="signup__dot signup__dot--ok"
                    ></span>

                    Akun berhasil didaftarkan!
                </template>
            </div>

            <div
                v-if="
                    camStatus === 'sampling' ||
                    camStatus === 'ready'
                "
                class="signup__progress"
            >
                <div
                    class="signup__progress-bar"
                    :style="{
                        width:
                            Math.min(
                                collectedSamples.length,
                                SAMPLE_TARGET
                            ) /
                                SAMPLE_TARGET *
                                100 +
                            '%',
                    }"
                ></div>
            </div>

            <div
                v-if="faceError"
                class="alert alert-danger py-2 fs-7"
            >
                {{ faceError }}
            </div>

            <div
                class="d-flex flex-column gap-2"
            >
                <button
                    class="auth-submit w-100"
                    :disabled="
                        camStatus !== 'ready' ||
                        registering
                    "
                    @click="finishRegistration"
                >
                    <span
                        v-if="registering"
                        class="spinner-border spinner-border-sm me-2"
                    ></span>

                    Buat Akun & Daftarkan Wajah
                </button>

                <button
                    v-if="
                        camStatus === 'ready'
                    "
                    type="button"
                    class="signup__retry"
                    @click="resetSamples"
                >
                    Kurang pas? Ulangi
                    pengambilan sample
                </button>

                <button
                    type="button"
                    class="signup__retry"
                    @click="
                        backToAccountStep
                    "
                >
                    ← Kembali ubah data akun
                </button>
            </div>
        </template>

        <!-- STEP 3 -->
        <template v-else>
            <div
                class="signup__header text-center"
            >
                <h2>Semua Siap!</h2>

                <p>
                    Akun kamu sudah dibuat dan
                    wajah sudah terdaftar.
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
import {
    ref,
    onBeforeUnmount,
} from "vue";

import * as faceapi from "face-api.js";
import axios from "@/libs/axios";
import { useRouter } from "vue-router";

const router = useRouter();

/*
|--------------------------------------------------------------------------
| STEP
|--------------------------------------------------------------------------
*/

const step = ref<
    "account" | "face" | "done"
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

function goToFaceStep() {
    accountError.value = "";

    const name =
        form.value.name.trim();

    const email =
        form.value.email.trim();

    const nis =
        form.value.nim_nis.trim();

    const school =
        form.value.asal_instansi.trim();

    /*
    |--------------------------------------------------------------------------
    | NAMA
    |--------------------------------------------------------------------------
    */

    if (!name) {
        accountError.value =
            "Nama lengkap wajib diisi.";

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    if (!email) {
        accountError.value =
            "Email wajib diisi.";

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | NIS
    |--------------------------------------------------------------------------
    */

    if (!nis) {
        accountError.value =
            "NIS wajib diisi.";

        return;
    }

    if (!/^\d+$/.test(nis)) {
        accountError.value =
            "NIS hanya boleh berisi angka.";

        return;
    }

    if (nis.length < 5) {
        accountError.value =
            "NIS minimal 5 angka.";

        return;
    }

    if (nis.length > 18) {
        accountError.value =
            "NIS maksimal 18 angka.";

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | SEKOLAH
    |--------------------------------------------------------------------------
    */

    if (!school) {
        accountError.value =
            "Asal sekolah wajib diisi.";

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | PASSWORD
    |--------------------------------------------------------------------------
    */

    if (!form.value.password) {
        accountError.value =
            "Password wajib diisi.";

        return;
    }

    if (
        form.value.password.length < 8
    ) {
        accountError.value =
            "Password minimal 8 karakter.";

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | KONFIRMASI PASSWORD
    |--------------------------------------------------------------------------
    */

    if (
        form.value.password !==
        form.value.password_confirmation
    ) {
        accountError.value =
            "Konfirmasi password tidak cocok.";

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | LANJUT KE FACE
    |--------------------------------------------------------------------------
    */

    step.value = "face";

    startFaceEnrollment();
}

/*
|--------------------------------------------------------------------------
| KEMBALI
|--------------------------------------------------------------------------
*/

function backToAccountStep() {
    stopCamera();

    collectedSamples.value = [];

    faceError.value = "";

    step.value = "account";
}

/*
|--------------------------------------------------------------------------
| FACE CONFIG
|--------------------------------------------------------------------------
*/

const MODEL_URL = "/models";

const SAMPLE_TARGET = 5;

const SAMPLE_INTERVAL_MS = 700;

/*
|--------------------------------------------------------------------------
| ELEMENT
|--------------------------------------------------------------------------
*/

const videoEl =
    ref<HTMLVideoElement | null>(
        null
    );

const canvasEl =
    ref<HTMLCanvasElement | null>(
        null
    );

/*
|--------------------------------------------------------------------------
| CAMERA STATUS
|--------------------------------------------------------------------------
*/

const camStatus = ref<
    | "loading_models"
    | "camera_error"
    | "no_face"
    | "sampling"
    | "ready"
    | "capturing"
    | "success"
>("loading_models");

const modelProgress = ref("");

const faceError = ref("");

const registering = ref(false);

/*
|--------------------------------------------------------------------------
| SAMPLE
|--------------------------------------------------------------------------
*/

const collectedSamples =
    ref<number[][]>([]);

/*
|--------------------------------------------------------------------------
| CAMERA VARIABLES
|--------------------------------------------------------------------------
*/

let stream: MediaStream | null =
    null;

let detectionTimer:
    number | null = null;

let samplingTimer:
    number | null = null;

let modelsLoaded = false;

/*
|--------------------------------------------------------------------------
| LOAD MODEL
|--------------------------------------------------------------------------
*/

async function loadFaceModels() {
    if (modelsLoaded) {
        return;
    }

    modelProgress.value =
        "Memuat model face detection...";

    await faceapi.nets.tinyFaceDetector.loadFromUri(
        MODEL_URL
    );

    modelProgress.value =
        "Memuat model face landmark...";

    await faceapi.nets.faceLandmark68Net.loadFromUri(
        MODEL_URL
    );

    modelProgress.value =
        "Memuat model face recognition...";

    await faceapi.nets.faceRecognitionNet.loadFromUri(
        MODEL_URL
    );

    modelsLoaded = true;
}

/*
|--------------------------------------------------------------------------
| START CAMERA
|--------------------------------------------------------------------------
*/

async function startCamera() {
    if (!videoEl.value) {
        return;
    }

    try {
        stopCamera();

        stream =
            await navigator.mediaDevices.getUserMedia(
                {
                    video: {
                        facingMode: "user",
                        width: {
                            ideal: 640,
                        },
                        height: {
                            ideal: 480,
                        },
                    },
                    audio: false,
                }
            );

        videoEl.value.srcObject =
            stream;

        await videoEl.value.play();

        camStatus.value =
            "no_face";

        startDetectionLoop();
    } catch (error) {
        console.error(
            "Gagal mengakses kamera:",
            error
        );

        camStatus.value =
            "camera_error";

        faceError.value =
            "Kamera tidak dapat diakses. Pastikan browser memiliki izin kamera.";
    }
}

/*
|--------------------------------------------------------------------------
| STOP CAMERA
|--------------------------------------------------------------------------
*/

function stopCamera() {
    if (
        detectionTimer !== null
    ) {
        window.clearInterval(
            detectionTimer
        );

        detectionTimer = null;
    }

    if (
        samplingTimer !== null
    ) {
        window.clearTimeout(
            samplingTimer
        );

        samplingTimer = null;
    }

    if (stream) {
        stream
            .getTracks()
            .forEach((track) => {
                track.stop();
            });

        stream = null;
    }

    if (videoEl.value) {
        videoEl.value.srcObject =
            null;
    }
}

/*
|--------------------------------------------------------------------------
| DETECTION LOOP
|--------------------------------------------------------------------------
*/

function startDetectionLoop() {
    if (!videoEl.value) {
        return;
    }

    if (
        detectionTimer !== null
    ) {
        window.clearInterval(
            detectionTimer
        );
    }

    detectionTimer =
        window.setInterval(
            async () => {
                await detectFace();
            },
            250
        );
}

/*
|--------------------------------------------------------------------------
| DETECT FACE
|--------------------------------------------------------------------------
*/

async function detectFace() {
    if (
        !videoEl.value ||
        videoEl.value.readyState < 2 ||
        registering.value ||
        step.value !== "face"
    ) {
        return;
    }

    try {
        const detection =
            await faceapi
                .detectSingleFace(
                    videoEl.value,
                    new faceapi.TinyFaceDetectorOptions(
                        {
                            inputSize: 320,
                            scoreThreshold: 0.5,
                        }
                    )
                )
                .withFaceLandmarks()
                .withFaceDescriptor();

        if (!detection) {
            camStatus.value =
                collectedSamples.value
                    .length >=
                SAMPLE_TARGET
                    ? "ready"
                    : "no_face";

            return;
        }

        if (
            collectedSamples.value
                .length >=
            SAMPLE_TARGET
        ) {
            camStatus.value =
                "ready";

            return;
        }

        camStatus.value =
            "sampling";

        scheduleSample(
            Array.from(
                detection.descriptor
            )
        );
    } catch (error) {
        console.error(
            "Face detection error:",
            error
        );
    }
}

/*
|--------------------------------------------------------------------------
| SAMPLE
|--------------------------------------------------------------------------
*/

function scheduleSample(
    descriptor: number[]
) {
    if (
        samplingTimer !== null
    ) {
        return;
    }

    samplingTimer =
        window.setTimeout(() => {
            samplingTimer = null;

            if (
                collectedSamples.value
                    .length >=
                SAMPLE_TARGET
            ) {
                camStatus.value =
                    "ready";

                return;
            }

            /*
             * Pastikan descriptor benar-benar
             * memiliki 128 angka.
             */

            if (
                descriptor.length !==
                128
            ) {
                faceError.value =
                    "Descriptor wajah tidak valid.";

                return;
            }

            collectedSamples.value.push(
                descriptor
            );

            if (
                collectedSamples.value
                    .length >=
                SAMPLE_TARGET
            ) {
                camStatus.value =
                    "ready";
            }
        }, SAMPLE_INTERVAL_MS);
}

/*
|--------------------------------------------------------------------------
| RESET SAMPLE
|--------------------------------------------------------------------------
*/

function resetSamples() {
    collectedSamples.value = [];

    faceError.value = "";

    camStatus.value =
        "no_face";

    if (
        samplingTimer !== null
    ) {
        window.clearTimeout(
            samplingTimer
        );

        samplingTimer = null;
    }
}

/*
|--------------------------------------------------------------------------
| REGISTRATION
|--------------------------------------------------------------------------
*/

async function finishRegistration() {
    faceError.value = "";

    if (
        collectedSamples.value.length <
        SAMPLE_TARGET
    ) {
        faceError.value =
            "Sample wajah belum mencukupi.";

        return;
    }

    /*
     * Pastikan semua descriptor
     * berjumlah 128 angka.
     */

    const invalidDescriptor =
        collectedSamples.value.some(
            (descriptor) =>
                !Array.isArray(
                    descriptor
                ) ||
                descriptor.length !==
                    128
        );

    if (invalidDescriptor) {
        faceError.value =
            "Data descriptor wajah tidak valid. Silakan ulangi pengambilan sample.";

        resetSamples();

        return;
    }

    if (registering.value) {
        return;
    }

    registering.value = true;

    camStatus.value =
        "capturing";

    try {
        const payload = {
            name:
                form.value.name.trim(),

            email:
                form.value.email.trim(),

            phone:
                form.value.phone.trim() ||
                null,

            nim_nis:
                form.value.nim_nis.trim(),

            asal_instansi:
                form.value.asal_instansi.trim(),

            password:
                form.value.password,

            password_confirmation:
                form.value
                    .password_confirmation,

            /*
             * PENTING:
             * Backend meminta "descriptors",
             * bukan "descriptor".
             */
            descriptors:
                collectedSamples.value,
        };

        const response =
            await axios.post(
                "/auth/register-with-face",
                payload
            );

        console.log(
            "Registration success:",
            response.data
        );

        camStatus.value =
            "success";

        stopCamera();

        step.value = "done";
    } catch (error: any) {
        console.error(
            "Registration error:",
            error
        );

        const response =
            error?.response;

        const errors =
            response?.data?.errors;

        if (errors) {
            const firstError =
                Object.values(errors)
                    .flat()
                    .find(Boolean);

            faceError.value =
                String(
                    firstError ||
                        "Data pendaftaran tidak valid."
                );
        } else {
            faceError.value =
                response?.data?.message ||
                "Pendaftaran gagal. Silakan coba lagi.";
        }

        camStatus.value =
            "ready";
    } finally {
        registering.value = false;
    }
}

/*
|--------------------------------------------------------------------------
| START FACE ENROLLMENT
|--------------------------------------------------------------------------
*/

async function startFaceEnrollment() {
    faceError.value = "";

    camStatus.value =
        "loading_models";

    try {
        await loadFaceModels();

        await startCamera();
    } catch (error) {
        console.error(
            "Face enrollment error:",
            error
        );

        camStatus.value =
            "camera_error";

        faceError.value =
            "Model face recognition gagal dimuat.";
    }
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

/*
|--------------------------------------------------------------------------
| CLEANUP
|--------------------------------------------------------------------------
*/

onBeforeUnmount(() => {
    stopCamera();
});
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