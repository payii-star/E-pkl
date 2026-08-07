<template>
    <div class="w-100 signup">
        <!-- STEP 1: Form akun -->
        <template v-if="step === 'account'">
            <div class="signup__header">
                <h2>Buat Akun Baru</h2>
                <p>Isi data di bawah untuk mendaftar sebagai karyawan/intern.</p>
            </div>

            <form class="auth-form" @submit.prevent="submitAccount">
                <div class="auth-field">
                    <label class="auth-field__label">Nama Lengkap</label>
                    <input v-model="form.name" type="text" class="auth-field__input" required autocomplete="name" />
                </div>
                <div class="auth-field">
                    <label class="auth-field__label">Email</label>
                    <input v-model="form.email" type="email" class="auth-field__input" required autocomplete="email" />
                </div>
                <div class="auth-field">
                    <label class="auth-field__label">No. Telepon (opsional)</label>
                    <input v-model="form.phone" type="tel" class="auth-field__input" autocomplete="tel" />
                </div>
                <div class="auth-field">
                    <label class="auth-field__label">Password</label>
                    <input v-model="form.password" type="password" class="auth-field__input" required minlength="8"
                        autocomplete="new-password" />
                </div>
                <div class="auth-field">
                    <label class="auth-field__label">Konfirmasi Password</label>
                    <input v-model="form.password_confirmation" type="password" class="auth-field__input" required
                        minlength="8" autocomplete="new-password" />
                </div>

                <div v-if="accountError" class="alert alert-danger py-2 fs-7">{{ accountError }}</div>

                <button type="submit" class="auth-submit w-100" :disabled="submitting">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    Daftar
                </button>
            </form>

            <div class="text-center mt-4">
                <router-link to="/sign-in" class="link-primary fw-bold">Sudah punya akun? Masuk</router-link>
            </div>
        </template>

        <!-- STEP 2: Pendaftaran wajah -->
        <template v-else-if="step === 'face'">
            <div class="signup__header">
                <h2>Daftarkan Wajah Kamu</h2>
                <p>
                    Supaya bisa login &amp; absen pakai wajah nanti. Boleh dilewati dan didaftarkan belakangan
                    lewat halaman profil.
                </p>
            </div>

            <div class="signup__cam" :class="`signup__cam--${camStatus}`">
                <video ref="videoEl" autoplay muted playsinline></video>
                <canvas ref="canvasEl"></canvas>

                <div v-if="camStatus === 'loading_models'" class="signup__overlay">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    {{ modelProgress || 'Menyiapkan kamera...' }}
                </div>
                <div v-else-if="camStatus === 'camera_error'" class="signup__overlay signup__overlay--err">
                    Tidak bisa mengakses kamera. Pastikan izin kamera diaktifkan.
                </div>
            </div>

            <div class="signup__status">
                <template v-if="camStatus === 'no_face'">
                    <span class="signup__dot signup__dot--warn"></span> Arahkan wajah ke kamera
                </template>
                <template v-else-if="camStatus === 'ready'">
                    <span class="signup__dot signup__dot--ok"></span> Wajah terdeteksi, siap didaftarkan
                </template>
                <template v-else-if="camStatus === 'capturing'">
                    <span class="signup__dot signup__dot--warn"></span> Menyimpan data wajah...
                </template>
                <template v-else-if="camStatus === 'success'">
                    <span class="signup__dot signup__dot--ok"></span> Wajah berhasil didaftarkan!
                </template>
            </div>

            <div v-if="faceError" class="alert alert-danger py-2 fs-7">{{ faceError }}</div>

            <div class="d-flex flex-column gap-2">
                <button class="auth-submit w-100" :disabled="camStatus !== 'ready' || registering"
                    @click="registerFace">
                    <span v-if="registering" class="spinner-border spinner-border-sm me-2"></span>
                    Daftarkan Wajah
                </button>
                <button type="button" class="auth-submit auth-submit--ghost w-100" @click="goToDashboard">
                    Lewati, daftarkan nanti saja
                </button>
            </div>
        </template>

        <!-- STEP 3: Selesai -->
        <template v-else>
            <div class="signup__header text-center">
                <h2>Semua Siap!</h2>
                <p>Akun kamu sudah dibuat{{ faceRegistered ? ' dan wajah sudah terdaftar' : '' }}.</p>
            </div>
            <button class="auth-submit w-100" @click="goToNext">
                {{ faceRegistered ? "Login dengan Wajah" : "Masuk ke Dashboard" }}
            </button>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount } from "vue";
import * as faceapi from "face-api.js";
import axios from "@/libs/axios";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";

const authStore = useAuthStore();
const router = useRouter();

const step = ref<"account" | "face" | "done">("account");

// ---- Step 1: form akun ----
const form = ref({
    name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
});
const submitting = ref(false);
const accountError = ref("");

async function submitAccount() {
    accountError.value = "";
    if (form.value.password !== form.value.password_confirmation) {
        accountError.value = "Konfirmasi password tidak cocok.";
        return;
    }
    submitting.value = true;
    try {
        const res = await axios.post("/auth/register", form.value);
        authStore.setAuth(res.data.user, res.data.token);
        step.value = "face";
        startFaceEnrollment();
    } catch (e: any) {
        accountError.value = e?.response?.data?.message || "Gagal membuat akun, coba lagi.";
    } finally {
        submitting.value = false;
    }
}

// ---- Step 2: pendaftaran wajah ----
const MODEL_URL = "/models";
const videoEl = ref<HTMLVideoElement | null>(null);
const canvasEl = ref<HTMLCanvasElement | null>(null);
const camStatus = ref<
    "loading_models" | "no_face" | "ready" | "capturing" | "success" | "camera_error"
>("loading_models");
const modelProgress = ref("");
const registering = ref(false);
const faceError = ref("");
const faceRegistered = ref(false);

let _stream: MediaStream | null = null;
let _loopInterval: number | null = null;
let _isDetecting = false;
let _lastDescriptor: number[] | null = null;

const TINY_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });

async function startFaceEnrollment() {
    try {
        modelProgress.value = "Memuat model deteksi wajah...";
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        modelProgress.value = "Memuat model landmark...";
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        modelProgress.value = "Memuat model pengenalan wajah...";
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    } catch (e) {
        console.error("Gagal memuat model face-api:", e);
        camStatus.value = "camera_error";
        return;
    }

    try {
        _stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 480, height: 360, facingMode: "user" },
        });
        if (!videoEl.value) return;
        videoEl.value.srcObject = _stream;
        await new Promise((resolve) => {
            videoEl.value!.onloadedmetadata = () => resolve(null);
            setTimeout(resolve, 3000);
        });
        await videoEl.value.play();
        camStatus.value = "no_face";
        _loopInterval = window.setInterval(detectLoop, 400);
    } catch (e) {
        console.error("Gagal akses kamera:", e);
        camStatus.value = "camera_error";
    }
}

async function detectLoop() {
    if (!videoEl.value || videoEl.value.readyState < 2 || _isDetecting) return;
    if (camStatus.value === "capturing" || camStatus.value === "success") return;
    _isDetecting = true;
    try {
        const det = await faceapi
            .detectSingleFace(videoEl.value, TINY_OPTS)
            .withFaceLandmarks()
            .withFaceDescriptor();

        drawBox(det);

        if (det) {
            _lastDescriptor = Array.from(det.descriptor);
            camStatus.value = "ready";
        } else {
            _lastDescriptor = null;
            camStatus.value = "no_face";
        }
    } catch {
        // frame gagal diproses, lewati saja, coba lagi di interval berikutnya
    } finally {
        _isDetecting = false;
    }
}

function drawBox(det: any) {
    if (!canvasEl.value || !videoEl.value) return;
    canvasEl.value.width = videoEl.value.videoWidth;
    canvasEl.value.height = videoEl.value.videoHeight;
    const ctx = canvasEl.value.getContext("2d");
    if (!ctx) return;
    ctx.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height);
    if (!det) return;
    const box = det.detection.box;
    ctx.strokeStyle = "#17c653";
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x, box.y, box.width, box.height);
}

function snapshotBase64(): string | null {
    if (!videoEl.value) return null;
    const c = document.createElement("canvas");
    c.width = videoEl.value.videoWidth;
    c.height = videoEl.value.videoHeight;
    const ctx = c.getContext("2d");
    if (!ctx) return null;
    ctx.drawImage(videoEl.value, 0, 0);
    return c.toDataURL("image/jpeg", 0.85);
}

async function registerFace() {
    if (!_lastDescriptor) return;
    registering.value = true;
    faceError.value = "";
    camStatus.value = "capturing";

    try {
        await axios.post("/face/register", {
            descriptor: _lastDescriptor,
            photo: snapshotBase64(),
        });
        camStatus.value = "success";
        faceRegistered.value = true;
        stopCamera();
        // Wajah sudah terdaftar -> lepas sesi supaya user wajib buktikan diri lewat Face Login
        authStore.logout();

        // Langsung arahkan ke form login wajah (tidak menampilkan kartu "Semua Siap!")
        setTimeout(() => {
            router.push("/face-login");
        }, 1200);
    } catch (e: any) {
        faceError.value = e?.response?.data?.message || "Gagal mendaftarkan wajah, coba lagi.";
        camStatus.value = "ready";
    } finally {
        registering.value = false;
    }
}

function stopCamera() {
    if (_loopInterval) {
        clearInterval(_loopInterval);
        _loopInterval = null;
    }
    _stream?.getTracks().forEach((t) => t.stop());
    _stream = null;
}

function goToDashboard() {
    stopCamera();
    router.push("/dashboard");
}

function goToNext() {
    stopCamera();
    if (faceRegistered.value) {
        router.push("/face-login");
    } else {
        router.push("/dashboard");
    }
}

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<style scoped>
.auth-form {
    font-family: "Manrope", sans-serif;
}

.auth-field {
    margin-bottom: 20px;
}

.auth-field__label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #152238;
    margin-bottom: 7px;
}

.auth-form .auth-field__input {
    width: 100%;
    border: 1.4px solid #e4dfd3 !important;
    background: #ffffff !important;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 14.5px;
    font-family: "Manrope", sans-serif;
    color: #152238 !important;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    box-shadow: none !important;
}

.auth-form .auth-field__input:focus {
    outline: none;
    border-color: #d98e3f !important;
    box-shadow: 0 0 0 3px rgba(217, 142, 63, 0.16) !important;
}

.auth-submit {
    width: 100%;
    border: none;
    border-radius: 10px;
    background: #152238;
    color: #f6f3ec;
    font-size: 14.5px;
    font-weight: 700;
    padding: 13px 0;
    margin-top: 8px;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.1s ease;
}

.auth-submit:hover {
    background: #1f3454;
}

.auth-submit:active {
    transform: translateY(1px);
}

.auth-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.auth-submit--ghost {
    background: transparent;
    color: #152238;
    border: 1.4px solid #e4dfd3;
}

.auth-submit--ghost:hover {
    background: #f6f3ec;
}

.signup__header h2 {
    margin-bottom: 4px;
}

.signup__header p {
    color: var(--bs-gray-600);
    font-size: 0.925rem;
    margin-bottom: 1.25rem;
}

.signup__cam {
    position: relative;
    width: 100%;
    max-width: 380px;
    aspect-ratio: 4 / 3;
    margin: 0 auto 1rem;
    border-radius: 12px;
    overflow: hidden;
    background: #10131a;
}

.signup__cam video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1);
}

.signup__cam canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transform: scaleX(-1);
}

.signup__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 1rem;
    color: #fff;
    background: rgba(16, 19, 26, 0.85);
    font-size: 0.875rem;
}

.signup__overlay--err {
    color: #f1416c;
}

.signup__status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--bs-gray-700);
    margin-bottom: 1rem;
    min-height: 24px;
    text-align: center;
}

.signup__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.signup__dot--ok { background: #17c653; }
.signup__dot--warn { background: #ffc700; }

.auth-submit--ghost {
    background: transparent;
    color: var(--bs-primary);
    border: 1px solid var(--bs-gray-300);
}
</style>
