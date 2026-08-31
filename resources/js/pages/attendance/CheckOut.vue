<template>
    <div class="row g-5">
        <div class="col-12 col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Absen Pulang</h2>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge" :class="statusBadgeClass">{{ statusLabel }}</span>
                    </div>
                </div>
                <div class="card-body pt-2 d-flex flex-column align-items-center">

                    <!-- Sudah selesai absen hari ini -->
                    <div v-if="alreadyDone" class="w-100 text-center py-10">
                        <div class="symbol symbol-60px mb-3 mx-auto">
                            <span class="symbol-label bg-light-success">
                                <KTIcon icon-name="check-circle" icon-class="fs-1 text-success" />
                            </span>
                        </div>
                        <div class="fw-bold fs-5 text-gray-800">Absensi hari ini sudah lengkap</div>
                        <div class="text-muted fs-7 mt-1">Sampai jumpa besok!</div>
                        <div class="text-muted fs-7 mt-3">
                            Absen masuk jam <span class="fw-semibold text-gray-700">{{ todayAttendance?.check_in_time ?? '-' }}</span>,
                            absen pulang jam <span class="fw-semibold text-gray-700">{{ todayAttendance?.check_out_time ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Kamera belum dibuka: tampilkan tombol -->
                    <div v-else-if="!cameraOpen" class="w-100 text-center py-10">
                        <div class="symbol symbol-60px mb-3 mx-auto">
                            <span class="symbol-label bg-light-primary">
                                <KTIcon icon-name="camera" icon-class="fs-1 text-primary" />
                            </span>
                        </div>
                        <div class="fw-bold fs-5 text-gray-800 mb-1">Siap absen pulang?</div>
                        <div class="text-muted fs-7 mb-2">Kamera akan menyala setelah kamu menekan tombol di bawah</div>
                        <div v-if="todayAttendance?.check_in_time" class="text-muted fs-7 mb-5">
                            Absen masuk tercatat jam <span class="fw-semibold text-gray-700">{{ todayAttendance.check_in_time }}</span>
                        </div>
                        <div v-else class="mb-5"></div>
                        <button class="btn btn-primary" :disabled="modelLoading" @click="openCamera">
                            <span v-if="modelLoading" class="spinner-border spinner-border-sm me-2"></span>
                            <KTIcon v-else icon-name="camera" icon-class="fs-4 me-2" />
                            {{ modelLoading ? (modelProgress || 'Memuat AI model...') : 'Buka Kamera' }}
                        </button>
                        <div v-if="faceMsg" :class="['alert w-100 py-2 fs-7 mt-5', faceMsgType === 'err' ? 'alert-danger' : 'alert-info']">
                            {{ faceMsg }}
                        </div>
                    </div>

                    <template v-else>
                        <!-- Kamera -->
                        <div class="cam-wrap position-relative w-100 mb-4" :class="'cam-wrap--' + camStatus">
                            <video ref="videoEl" autoplay muted playsinline class="cam-video rounded" />
                            <canvas ref="canvasEl" class="cam-canvas position-absolute top-0 start-0 w-100 h-100" />

                            <!-- Overlay sukses -->
                            <div v-if="recognizedUser" class="cam-success-overlay rounded">
                                <div class="symbol symbol-60px mb-2">
                                    <span class="symbol-label bg-success">
                                        <KTIcon icon-name="check" icon-class="fs-1 text-white" />
                                    </span>
                                </div>
                                <div class="fw-bold text-white fs-5">{{ recognizedUser.name }}</div>
                                <div class="text-white fs-7 mt-1">{{ attendanceMsg }}</div>
                            </div>

                            <!-- Status pill -->
                            <div class="cam-pill" :class="'cam-pill--' + camStatus">
                                <span class="cam-pill__dot"></span>
                                <span v-if="camStatus === 'detecting'">Mendeteksi wajah...</span>
                                <span v-else-if="camStatus === 'matching'">Mencocokkan wajah{{ matchedName ? `: ${matchedName}` : '' }}...</span>
                                <span v-else-if="camStatus === 'no_face'">Arahkan wajah ke kamera</span>
                                <span v-else-if="camStatus === 'processing'">Memproses...</span>
                                <span v-else-if="camStatus === 'success'">Berhasil!</span>
                                <span v-else-if="camStatus === 'failed'">Wajah tidak dikenali</span>
                            </div>
                        </div>

                        <!-- Tombol tutup kamera -->
                        <button v-if="camStatus !== 'success'" class="btn btn-sm btn-light mb-3" @click="closeCamera">
                            <KTIcon icon-name="cross" icon-class="fs-6 me-1" />
                            Tutup Kamera
                        </button>

                        <!-- Info absensi hari ini -->
                        <div v-if="todayAttendance" class="w-100 mb-3">
                            <div class="d-flex gap-3">
                                <div class="flex-fill bg-light-success rounded p-3 text-center">
                                    <div class="text-muted fs-8 fw-semibold mb-1">MASUK</div>
                                    <div class="fw-bold text-success fs-5">{{ todayAttendance.check_in_time ?? '-' }}</div>
                                </div>
                                <div class="flex-fill bg-light-danger rounded p-3 text-center">
                                    <div class="text-muted fs-8 fw-semibold mb-1">KELUAR</div>
                                    <div class="fw-bold text-danger fs-5">{{ todayAttendance.check_out_time ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Pesan error/info -->
                        <div v-if="faceMsg" :class="['alert w-100 py-2 fs-7', faceMsgType === 'err' ? 'alert-danger' : 'alert-info']">
                            {{ faceMsg }}
                        </div>

                        <!-- Guide -->
                        <div v-if="!recognizedUser && !faceMsg" class="text-center text-muted fs-7">
                            <KTIcon icon-name="eye" icon-class="fs-3 me-1" />
                            Posisikan wajah di tengah kamera — sistem otomatis mengenali & mencatat absen pulang
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import * as faceapi from 'face-api.js'
import axios from '@/libs/axios'

// ─── Constants ────────────────────────────────────────────────────────────────
const MODEL_URL = '/models'
const MATCH_THRESHOLD = 0.5
const CONSECUTIVE_MATCHES_NEEDED = 3
const TINY_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })

// ─── Helpers ─────────────────────────────────────────────────────────────────
function capturePhoto(video: HTMLVideoElement): string | null {
    try {
        const c = document.createElement('canvas')
        c.width = video.videoWidth || 480
        c.height = video.videoHeight || 360
        const ctx = c.getContext('2d')
        if (!ctx) return null
        ctx.translate(c.width, 0); ctx.scale(-1, 1)
        ctx.drawImage(video, 0, 0, c.width, c.height)
        return c.toDataURL('image/jpeg', 0.85)
    } catch { return null }
}

// ─── Refs ─────────────────────────────────────────────────────────────────────
const videoEl  = ref<HTMLVideoElement | null>(null)
const canvasEl = ref<HTMLCanvasElement | null>(null)

const modelLoading    = ref(false)
const modelProgress   = ref('')
const cameraOpen       = ref(false)
const camStatus        = ref<'detecting' | 'matching' | 'no_face' | 'processing' | 'success' | 'failed'>('detecting')
const faceMsg          = ref('')
const faceMsgType      = ref('info')
const recognizedUser   = ref<any>(null)
const matchedName      = ref('')
const attendanceMsg    = ref('')
const todayAttendance  = ref<any>(null)

let _stream: MediaStream | null = null
let _detectInterval: number | null = null
let _isDetecting = false
let _isSubmitting = false
let _profiles: Array<{ user_id: number; name: string; descriptor: number[] }> = []
let _consecutiveUserId: number | null = null
let _consecutiveCount = 0

// ─── Computed ─────────────────────────────────────────────────────────────────
const alreadyDone = computed(() =>
    !!todayAttendance.value?.check_in_time && !!todayAttendance.value?.check_out_time
)

const statusLabel = computed(() => {
    if (!todayAttendance.value) return 'Belum Absen'
    if (!todayAttendance.value.check_out_time) return 'Sudah Masuk'
    return 'Selesai'
})

const statusBadgeClass = computed(() => {
    if (!todayAttendance.value) return 'badge-light-warning'
    if (!todayAttendance.value.check_out_time) return 'badge-light-primary'
    return 'badge-light-success'
})

// ─── Data loading ─────────────────────────────────────────────────────────────
async function fetchToday() {
    try {
        const res = await axios.get('/attendances/today')
        todayAttendance.value = res.data.data ?? null
    } catch (e) {
        console.error('Gagal ambil data absensi hari ini:', e)
    }
}

async function loadModels() {
    try {
        modelProgress.value = 'Memuat model deteksi wajah...'
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL)
        modelProgress.value = 'Memuat model landmark...'
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
        modelProgress.value = 'Memuat model pengenalan wajah...'
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
        modelLoading.value = false
    } catch (e) {
        console.error('Gagal memuat model face-api:', e)
        faceMsg.value = 'Gagal memuat model AI. Refresh halaman ini.'
        faceMsgType.value = 'err'
    }
}

async function loadProfiles() {
    try {
        const res = await axios.get('/face/profiles')
        _profiles = (res.data.data ?? []).filter((p: any) => Array.isArray(p.descriptor))
    } catch (e) {
        console.error('Gagal memuat daftar wajah terdaftar:', e)
        _profiles = []
    }
}

// ─── Camera ───────────────────────────────────────────────────────────────────
async function startCamera() {
    try {
        _stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 480, height: 360, facingMode: 'user' },
        })
        if (!videoEl.value) return
        videoEl.value.srcObject = _stream
        await new Promise((resolve) => {
            videoEl.value!.onloadedmetadata = () => resolve(null)
            setTimeout(resolve, 3000)
        })
        await videoEl.value.play()
        camStatus.value = 'detecting'
        startDetectLoop()
    } catch (e) {
        console.error('Gagal akses kamera:', e)
        faceMsg.value = 'Tidak bisa mengakses kamera. Pastikan izin kamera diaktifkan di browser.'
        faceMsgType.value = 'err'
    }
}

function stopCamera() {
    if (_detectInterval) {
        clearInterval(_detectInterval)
        _detectInterval = null
    }
    _stream?.getTracks().forEach((t) => t.stop())
    _stream = null
}

function startDetectLoop() {
    if (_detectInterval) clearInterval(_detectInterval)
    _detectInterval = window.setInterval(async () => {
        if (!videoEl.value || videoEl.value.readyState < 2 || _isDetecting || _isSubmitting) return
        _isDetecting = true
        try {
            await detectFrame()
        } finally {
            _isDetecting = false
        }
    }, 400)
}

async function detectFrame() {
    if (!videoEl.value) return

    let det: any = null
    try {
        det = await faceapi
            .detectSingleFace(videoEl.value, TINY_OPTS)
            .withFaceLandmarks()
            .withFaceDescriptor()
    } catch {
        det = null
    }

    drawBox(det)

    if (!det) {
        camStatus.value = 'detecting'
        matchedName.value = ''
        _consecutiveUserId = null
        _consecutiveCount = 0
        return
    }

    const liveDescriptor = Array.from(det.descriptor) as number[]
    let best: { user_id: number; name: string; distance: number } | null = null

    for (const profile of _profiles) {
        const distance = faceapi.euclideanDistance(liveDescriptor, profile.descriptor)
        if (!best || distance < best.distance) {
            best = { user_id: profile.user_id, name: profile.name, distance }
        }
    }

    if (!best || best.distance > MATCH_THRESHOLD) {
        camStatus.value = 'failed'
        matchedName.value = ''
        _consecutiveUserId = null
        _consecutiveCount = 0
        return
    }

    camStatus.value = 'matching'
    matchedName.value = best.name

    if (_consecutiveUserId === best.user_id) {
        _consecutiveCount += 1
    } else {
        _consecutiveUserId = best.user_id
        _consecutiveCount = 1
    }

    if (_consecutiveCount >= CONSECUTIVE_MATCHES_NEEDED) {
        await doCheckOut(best)
    }
}

function drawBox(det: any) {
    if (!canvasEl.value || !videoEl.value) return
    canvasEl.value.width = videoEl.value.videoWidth
    canvasEl.value.height = videoEl.value.videoHeight
    const ctx = canvasEl.value.getContext('2d')
    if (!ctx) return
    ctx.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height)
    if (!det) return

    const box = det.detection.box
    ctx.strokeStyle = camStatus.value === 'success' ? '#17c653' : camStatus.value === 'failed' ? '#f1416c' : '#009ef7'
    ctx.lineWidth = 3
    ctx.strokeRect(box.x, box.y, box.width, box.height)
}

// ─── Check-out ────────────────────────────────────────────────────────────────
async function doCheckOut(profile: { user_id: number; name: string }) {
    if (_isSubmitting) return
    _isSubmitting = true
    camStatus.value = 'processing'
    recognizedUser.value = { name: profile.name }

    try {
        const photo = videoEl.value ? capturePhoto(videoEl.value) : null
        const res = await axios.post('/attendances/check-out', { photo })
        attendanceMsg.value = res.data?.message ?? 'Absen pulang berhasil'
        camStatus.value = 'success'
        stopCamera()
        await fetchToday()
    } catch (e: any) {
        faceMsg.value = e.response?.data?.message ?? e.message ?? 'Gagal mencatat absen pulang'
        faceMsgType.value = 'err'
        recognizedUser.value = null
        camStatus.value = 'detecting'
        _consecutiveUserId = null
        _consecutiveCount = 0
        setTimeout(() => { faceMsg.value = '' }, 4000)
    } finally {
        _isSubmitting = false
    }
}

// ─── Buka / tutup kamera (dipicu tombol) ──────────────────────────────────────
async function openCamera() {
    faceMsg.value = ''
    modelLoading.value = true
    try {
        await loadModels()
        await loadProfiles()
        cameraOpen.value = true
        await startCamera()
    } finally {
        modelLoading.value = false
    }
}

function closeCamera() {
    stopCamera()
    cameraOpen.value = false
    camStatus.value = 'detecting'
    recognizedUser.value = null
    matchedName.value = ''
    _consecutiveUserId = null
    _consecutiveCount = 0
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    await fetchToday()
})

onUnmounted(() => {
    stopCamera()
})
</script>

<style scoped>
/* ── Camera ── */
.cam-wrap {
    aspect-ratio: 4/3;
    background: #0d0f14;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e4e6ef;
    transition: border-color .3s, box-shadow .3s;
}
.cam-wrap--matching,
.cam-wrap--success    { border-color: #17c653; box-shadow: 0 0 20px rgba(23,198,83,.2); }
.cam-wrap--failed      { border-color: #f64e60; box-shadow: 0 0 16px rgba(246,78,96,.2); }
.cam-wrap--processing { border-color: #009ef7; box-shadow: 0 0 20px rgba(0,158,247,.2); }

.cam-video  { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block; }
.cam-canvas { transform: scaleX(-1); pointer-events: none; }

/* Success overlay */
.cam-success-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,.7);
    backdrop-filter: blur(6px);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 8px;
    z-index: 10;
}

/* Status pill */
.cam-pill {
    position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
    display: flex; align-items: center; gap: 6px;
    background: rgba(0,0,0,.75); backdrop-filter: blur(6px);
    border-radius: 20px; padding: 5px 14px;
    font-size: 12px; font-weight: 600; color: #cdcdde;
    white-space: nowrap; z-index: 5;
    border: 1px solid rgba(255,255,255,.1);
}
.cam-pill__dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #5e6278; flex-shrink: 0;
}
.cam-pill--matching   .cam-pill__dot,
.cam-pill--success    .cam-pill__dot { background: #17c653; box-shadow: 0 0 5px #17c653; animation: blink .9s ease-in-out infinite; }
.cam-pill--failed     .cam-pill__dot { background: #f64e60; }
.cam-pill--processing .cam-pill__dot { background: #009ef7; animation: blink .5s ease-in-out infinite; }
.cam-pill--matching   { color: #17c653; }
.cam-pill--success    { color: #17c653; }
.cam-pill--failed     { color: #f64e60; }
.cam-pill--processing { color: #009ef7; }

@keyframes blink { 0%,100% { opacity: 1 } 50% { opacity: .3 } }
</style>