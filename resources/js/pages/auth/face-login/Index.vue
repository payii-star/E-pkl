<template>
    <div class="w-100 face-login">
        <div class="face-login__header">
            <h2>Masuk dengan Wajah</h2>
            <p>Posisikan wajahmu di depan kamera. Sistem akan mengenali kamu secara otomatis.</p>
        </div>

        <!-- Kamera + overlay -->
        <div class="face-login__cam" :class="`face-login__cam--${status}`">
            <video ref="videoEl" autoplay muted playsinline></video>
            <canvas ref="canvasEl"></canvas>

            <div v-if="status === 'loading_models'" class="face-login__overlay">
                <span class="spinner-border spinner-border-sm me-2"></span>
                {{ modelProgress || 'Menyiapkan kamera...' }}
            </div>
            <div v-else-if="status === 'camera_error'" class="face-login__overlay face-login__overlay--err">
                Tidak bisa mengakses kamera. Pastikan izin kamera diaktifkan di browser.
            </div>
        </div>

        <!-- Status text -->
        <div class="face-login__status">
            <template v-if="status === 'scanning'">
                <span class="face-login__dot face-login__dot--info"></span>
                Mencari wajah...
            </template>
            <template v-else-if="status === 'matching'">
                <span class="face-login__dot face-login__dot--warn"></span>
                Mencocokkan wajah{{ matchedName ? `: ${matchedName}` : '' }}...
            </template>
            <template v-else-if="status === 'matched'">
                <span class="face-login__dot face-login__dot--ok"></span>
                Wajah dikenali sebagai <strong>{{ matchedName }}</strong> — masuk...
            </template>
            <template v-else-if="status === 'unknown'">
                <span class="face-login__dot face-login__dot--err"></span>
                Wajah tidak dikenali. Coba posisikan ulang, atau masuk pakai email.
            </template>
            <template v-else-if="status === 'no_profiles'">
                <span class="face-login__dot face-login__dot--err"></span>
                Belum ada wajah yang terdaftar di sistem. Hubungi admin.
            </template>
            <template v-else-if="status === 'login_failed'">
                <span class="face-login__dot face-login__dot--err"></span>
                {{ errorMsg || 'Gagal masuk, coba lagi.' }}
            </template>
        </div>

        <div class="face-login__actions">
            <router-link to="/sign-in" class="auth-submit auth-submit--ghost">
                Masuk pakai Email
            </router-link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import * as faceapi from "face-api.js";
import axios from "@/libs/axios";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";

const authStore = useAuthStore();
const router = useRouter();

const MODEL_URL = "/models";
// Jarak Euclidean maksimum supaya dianggap "cocok". Makin kecil = makin ketat.
const MATCH_THRESHOLD = 0.5;
// Berapa kali berturut-turut harus dapat orang yang sama sebelum benar-benar login,
// supaya tidak salah login gara-gara 1 frame yang kebetulan mirip.
const CONSECUTIVE_MATCHES_NEEDED = 3;

const videoEl = ref<HTMLVideoElement | null>(null);
const canvasEl = ref<HTMLCanvasElement | null>(null);

const status = ref<
    "loading_models" | "scanning" | "matching" | "matched" | "unknown" | "no_profiles" | "camera_error" | "login_failed"
>("loading_models");
const modelProgress = ref("");
const matchedName = ref("");
const errorMsg = ref("");

let _stream: MediaStream | null = null;
let _detectInterval: number | null = null;
let _isDetecting = false;
let _isLoggingIn = false;
let _profiles: Array<{ user_id: number; name: string; descriptor: number[] }> = [];
let _consecutiveUserId: number | null = null;
let _consecutiveCount = 0;

const TINY_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });

onMounted(async () => {
    await loadModels();
    await loadProfiles();
    if (_profiles.length === 0) {
        status.value = "no_profiles";
        return;
    }
    await startCamera();
});

onUnmounted(() => {
    stopCamera();
});

async function loadModels() {
    try {
        modelProgress.value = "Memuat model deteksi wajah...";
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        modelProgress.value = "Memuat model landmark...";
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        modelProgress.value = "Memuat model pengenalan wajah...";
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
    } catch (e) {
        console.error("Gagal memuat model face-api:", e);
        status.value = "camera_error";
    }
}

async function loadProfiles() {
    try {
        const res = await axios.get("/face/profiles");
        _profiles = (res.data.data ?? []).filter((p: any) => Array.isArray(p.descriptor));
    } catch (e) {
        console.error("Gagal memuat daftar wajah terdaftar:", e);
        _profiles = [];
    }
}

async function startCamera() {
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
        status.value = "scanning";
        startDetectLoop();
    } catch (e) {
        console.error("Gagal akses kamera:", e);
        status.value = "camera_error";
    }
}

function stopCamera() {
    if (_detectInterval) {
        clearInterval(_detectInterval);
        _detectInterval = null;
    }
    _stream?.getTracks().forEach((t) => t.stop());
    _stream = null;
}

function startDetectLoop() {
    if (_detectInterval) clearInterval(_detectInterval);
    _detectInterval = window.setInterval(async () => {
        if (!videoEl.value || videoEl.value.readyState < 2 || _isDetecting || _isLoggingIn) return;
        _isDetecting = true;
        try {
            await detectFrame();
        } finally {
            _isDetecting = false;
        }
    }, 400);
}

async function detectFrame() {
    if (!videoEl.value) return;

    let det: any = null;
    try {
        det = await faceapi
            .detectSingleFace(videoEl.value, TINY_OPTS)
            .withFaceLandmarks()
            .withFaceDescriptor();
    } catch {
        det = null;
    }

    drawBox(det);

    if (!det) {
        status.value = "scanning";
        matchedName.value = "";
        _consecutiveUserId = null;
        _consecutiveCount = 0;
        return;
    }

    // Cari kandidat dengan jarak Euclidean terkecil ke semua wajah terdaftar
    const liveDescriptor = Array.from(det.descriptor) as number[];
    let best: { user_id: number; name: string; distance: number } | null = null;

    for (const profile of _profiles) {
        const distance = faceapi.euclideanDistance(liveDescriptor, profile.descriptor);
        if (!best || distance < best.distance) {
            best = { user_id: profile.user_id, name: profile.name, distance };
        }
    }

    if (!best || best.distance > MATCH_THRESHOLD) {
        status.value = "unknown";
        matchedName.value = "";
        _consecutiveUserId = null;
        _consecutiveCount = 0;
        return;
    }

    status.value = "matching";
    matchedName.value = best.name;

    if (_consecutiveUserId === best.user_id) {
        _consecutiveCount += 1;
    } else {
        _consecutiveUserId = best.user_id;
        _consecutiveCount = 1;
    }

    if (_consecutiveCount >= CONSECUTIVE_MATCHES_NEEDED) {
        await doLogin(best.user_id, best.name);
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
    ctx.strokeStyle = status.value === "matched" ? "#17c653" : status.value === "unknown" ? "#f1416c" : "#009ef7";
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x, box.y, box.width, box.height);
}

async function doLogin(userId: number, name: string) {
    if (_isLoggingIn) return;
    _isLoggingIn = true;
    status.value = "matched";
    matchedName.value = name;
    stopCamera();

    try {
        const res = await axios.post("/face/login", { user_id: userId });
        authStore.setAuth(res.data.user, res.data.token);
        router.push("/dashboard");
    } catch (e: any) {
        errorMsg.value = e?.response?.data?.message || "Gagal masuk, coba lagi.";
        status.value = "login_failed";
        _isLoggingIn = false;
        // Kasih kesempatan coba lagi
        setTimeout(async () => {
            _consecutiveUserId = null;
            _consecutiveCount = 0;
            await startCamera();
        }, 2500);
    }
}
</script>

<style scoped>
.face-login__header h2 {
    margin-bottom: 4px;
}

.face-login__header p {
    color: var(--bs-gray-600);
    font-size: 0.925rem;
    margin-bottom: 1.25rem;
}

.face-login__cam {
    position: relative;
    width: 100%;
    max-width: 420px;
    aspect-ratio: 4 / 3;
    margin: 0 auto 1rem;
    border-radius: 12px;
    overflow: hidden;
    background: #10131a;
}

.face-login__cam video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1);
}

.face-login__cam canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    transform: scaleX(-1);
}

.face-login__overlay {
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

.face-login__overlay--err {
    color: #f1416c;
}

.face-login__status {
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

.face-login__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.face-login__dot--info { background: #009ef7; }
.face-login__dot--warn { background: #ffc700; }
.face-login__dot--ok { background: #17c653; }
.face-login__dot--err { background: #f1416c; }

.face-login__actions {
    display: flex;
    justify-content: center;
}

.auth-submit--ghost {
    background: transparent;
    color: var(--bs-primary);
    border: 1px solid var(--bs-gray-300);
}
</style>
