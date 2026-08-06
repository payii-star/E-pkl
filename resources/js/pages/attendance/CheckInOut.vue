<template>
    <div class="row g-5">

        <!-- ══ KAMERA + STATUS ══ -->
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Absensi Wajah</h2>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge" :class="statusBadgeClass">{{ statusLabel }}</span>
                    </div>
                </div>
                <div class="card-body pt-2 d-flex flex-column align-items-center">

                    <!-- Loading model -->
                    <div v-if="modelLoading" class="w-100 text-center py-10">
                        <div class="spinner-border text-primary mb-4" style="width:3rem;height:3rem;"></div>
                        <div class="text-gray-600 fw-semibold">Memuat AI model...</div>
                        <div class="text-muted fs-7 mt-1">{{ modelProgress }}</div>
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
                                <span v-else-if="camStatus === 'liveness'">Ikuti instruksi</span>
                                <span v-else-if="camStatus === 'ready'">Wajah terdeteksi</span>
                                <span v-else-if="camStatus === 'no_face'">Arahkan wajah ke kamera</span>
                                <span v-else-if="camStatus === 'processing'">Memproses...</span>
                                <span v-else-if="camStatus === 'success'">Berhasil!</span>
                                <span v-else-if="camStatus === 'failed'">Wajah tidak dikenali</span>
                                <span v-else-if="camStatus === 'liveness_fail'">Gagal, coba lagi</span>
                            </div>
                        </div>

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
                            Posisikan wajah di tengah kamera — sistem otomatis mengenali
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- ══ LIVENESS + INFO ══ -->
        <div class="col-12 col-lg-7">

            <!-- Liveness challenge -->
            <div v-if="livenessActive && !livenessCompleted" class="card mb-5 border border-warning">
                <div class="card-header border-0 pt-5 pb-0">
                    <div class="card-title">
                        <KTIcon icon-name="shield-tick" icon-class="fs-2 text-warning me-2" />
                        <h3 class="fw-bold mb-0">Verifikasi Liveness</h3>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge badge-light-warning">
                            {{ currentChallengeIndex + 1 }} / {{ challenges.length }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Progress dots -->
                    <div class="d-flex gap-2 mb-5">
                        <div v-for="(ch, i) in challenges" :key="i"
                            class="lv-dot flex-fill h-8px rounded"
                            :class="i < currentChallengeIndex ? 'bg-success' : i === currentChallengeIndex ? 'bg-warning' : 'bg-light'">
                        </div>
                    </div>

                    <!-- Instruksi -->
                    <div class="d-flex align-items-center gap-4 mb-5" :class="{ 'animate__animated animate__shakeX': challengeFailed }">
                        <div class="symbol symbol-60px">
                            <span class="symbol-label bg-light-warning">
                                <KTIcon :icon-name="challengeIcon(currentChallenge?.type)" icon-class="fs-1 text-warning" />
                            </span>
                        </div>
                        <div>
                            <div class="fw-bold fs-4 text-gray-800">{{ currentChallenge?.instruction }}</div>
                            <div class="text-muted fs-7 mt-1">{{ currentChallenge?.hint }}</div>
                        </div>
                    </div>

                    <!-- Blink state -->
                    <div v-if="currentChallenge?.type === 'blink'" class="mb-4">
                        <div v-if="!earBaselineReady" class="text-muted fs-7">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Kalibrasi mata... ({{ Math.min(earBaselineFramesDisplay, 10) }}/10)
                        </div>
                        <div v-else-if="eyeOpen && blinkCount === 0" class="text-muted fs-7">
                            Kedipkan mata Anda
                        </div>
                        <div v-else-if="!eyeOpen" class="badge badge-light-warning fs-7">
                            Mata tertutup...
                        </div>
                        <div v-else-if="blinkCount === 1 && currentChallenge?.double" class="badge badge-light-success fs-7">
                            ✓ Kedipan 1 — sekali lagi
                        </div>
                        <!-- Debug EOS -->
                        <div class="mt-2 d-flex gap-3 fs-8 text-muted">
                            <span>L: <b :class="earScoreL < earCloseThresh ? 'text-danger' : 'text-success'">{{ earScoreL.toFixed(3) }}</b></span>
                            <span>R: <b :class="earScoreR < earCloseThresh ? 'text-danger' : 'text-success'">{{ earScoreR.toFixed(3) }}</b></span>
                            <span>base: <b>{{ earBaseline.toFixed(3) }}</b></span>
                            <span>tutup &lt; <b>{{ earCloseThresh.toFixed(3) }}</b></span>
                        </div>
                    </div>

                    <!-- Return to center -->
                    <div v-if="returnPhase" class="alert alert-success py-2 fs-7 mb-4">
                        Bagus! Sekarang <b>kembali ke tengah</b>
                    </div>

                    <!-- Timer -->
                    <div class="mb-1">
                        <div class="progress h-8px">
                            <div class="progress-bar"
                                :class="timerPct < 40 ? 'bg-danger' : timerPct < 70 ? 'bg-warning' : 'bg-success'"
                                :style="{ width: timerPct + '%', transition: 'width 0.1s linear' }">
                            </div>
                        </div>
                    </div>
                    <div class="text-end fs-8 text-muted">{{ Math.ceil(secsLeft) }}s</div>
                </div>
            </div>

            <!-- Info & panduan -->
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h3 class="fw-bold">Panduan Absensi</h3>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex flex-column gap-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="symbol symbol-35px flex-shrink-0">
                                <span class="symbol-label bg-light-primary">
                                    <KTIcon icon-name="camera" icon-class="fs-4 text-primary" />
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold text-gray-800">Izinkan akses kamera</div>
                                <div class="text-muted fs-7">Pastikan browser mendapat izin menggunakan kamera perangkat Anda</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="symbol symbol-35px flex-shrink-0">
                                <span class="symbol-label bg-light-warning">
                                    <KTIcon icon-name="eye" icon-class="fs-4 text-warning" />
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold text-gray-800">Posisikan wajah dengan benar</div>
                                <div class="text-muted fs-7">Pastikan wajah terlihat jelas, pencahayaan cukup, tidak ada yang menutupi</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="symbol symbol-35px flex-shrink-0">
                                <span class="symbol-label bg-light-success">
                                    <KTIcon icon-name="check-circle" icon-class="fs-4 text-success" />
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold text-gray-800">Ikuti instruksi liveness</div>
                                <div class="text-muted fs-7">Setelah wajah dikenali, ikuti instruksi gerakan untuk verifikasi keamanan</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="symbol symbol-35px flex-shrink-0">
                                <span class="symbol-label bg-light-info">
                                    <KTIcon icon-name="information" icon-class="fs-4 text-info" />
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold text-gray-800">Waktu absensi</div>
                                <div class="text-muted fs-7">Absen masuk sebelum jam 07:00 dianggap tepat waktu. Check-out minimal 3 jam setelah masuk</div>
                            </div>
                        </div>
                    </div>

                    <!-- Status hari ini -->
                    <div class="separator my-5"></div>
                    <div class="fw-bold text-gray-700 mb-3">Status Hari Ini</div>
                    <div v-if="!todayAttendance" class="text-muted fs-7">
                        <KTIcon icon-name="information" icon-class="fs-5 me-1 text-warning" />
                        Belum ada catatan absensi hari ini
                    </div>
                    <div v-else class="d-flex flex-column gap-2 fs-7">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status Masuk</span>
                            <span class="badge" :class="todayAttendance.status === 'on_time' ? 'badge-light-success' : todayAttendance.status === 'late' ? 'badge-light-danger' : 'badge-light-warning'">
                                {{ statusLabelMap[todayAttendance.status] ?? todayAttendance.status }}
                            </span>
                        </div>
                        <div v-if="todayAttendance.late_minutes > 0" class="d-flex justify-content-between">
                            <span class="text-muted">Keterlambatan</span>
                            <span class="text-danger fw-semibold">{{ todayAttendance.late_minutes }} menit</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status Pulang</span>
                            <span class="badge" :class="todayAttendance.check_out_time ? 'badge-light-primary' : 'badge-light-warning'">
                                {{ todayAttendance.check_out_time ? 'Sudah Check-out' : 'Belum Check-out' }}
                            </span>
                        </div>
                    </div>
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
const MODEL_URL      = '/models'
const THRESHOLD      = 0.45
const CHALLENGE_SECS = 15

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

function euclidean(a: number[], b: number[]): number {
    let s = 0
    for (let i = 0; i < a.length; i++) s += (a[i] - b[i]) ** 2
    return Math.sqrt(s)
}

// ── Eye Openness Score ────────────────────────────────────────────────────────
// Lebih akurat dari EAR untuk face-api.js
// Nilai terbuka: ~0.04-0.08 | tertutup: ~0.005-0.015
function eyeOpenScore(eye: any[], faceWidth: number): number {
    if (faceWidth < 1) return 0.06
    const ys   = eye.map((p: any) => p.y)
    const bboxH = (Math.max(...ys) - Math.min(...ys)) / faceWidth
    const A = Math.abs(eye[1].y - eye[5].y)
    const B = Math.abs(eye[2].y - eye[4].y)
    const pairH = ((A + B) / 2.0) / faceWidth
    return Math.max(bboxH, pairH)
}

// ── Challenge definitions ─────────────────────────────────────────────────────
const ALL_CHALLENGES = [
    { type: 'blink',      instruction: 'Kedipkan mata sekali',       hint: 'Tutup dan buka mata secara natural' },
    { type: 'turn_left',  instruction: 'Palingkan kepala ke KIRI',   hint: 'Putar kepala ke kiri' },
    { type: 'turn_right', instruction: 'Palingkan kepala ke KANAN',  hint: 'Putar kepala ke kanan' },
    { type: 'nod_up',     instruction: 'Tengadahkan kepala ke ATAS', hint: 'Angkat dagu ke atas' },
    { type: 'nod_down',   instruction: 'Tundukkan kepala ke BAWAH',  hint: 'Turunkan dagu ke bawah' },
    { type: 'blink',      instruction: 'Kedipkan mata DUA kali',     hint: 'Dua kedipan berturut-turut', double: true },
]

function seededRng(seed: number) {
    let s = seed
    return () => { s = (s * 1664525 + 1013904223) & 0xffffffff; return (s >>> 0) / 0xffffffff }
}

function pickChallenges(n = 3): any[] {
    const seed = Date.now() ^ (Math.random() * 0xffffffff | 0)
    const rng  = seededRng(seed)
    const pool = ALL_CHALLENGES.filter(c => c.type !== 'blink')
    const shuffled = [...pool].sort(() => rng() - 0.5)
    const result: any[] = [{ ...ALL_CHALLENGES[0] }]
    let last = 'blink'
    for (const ch of shuffled) {
        if (result.length >= n) break
        if (ch.type === last) continue
        result.push({ ...ch }); last = ch.type
    }
    return result
}

function challengeIcon(type?: string): string {
    const map: Record<string, string> = {
        blink:      'eye',
        turn_left:  'arrow-left',
        turn_right: 'arrow-right',
        nod_up:     'arrow-up',
        nod_down:   'arrow-down',
    }
    return map[type ?? ''] ?? 'question'
}

const statusLabelMap: Record<string, string> = {
    on_time: 'Tepat Waktu',
    late:    'Terlambat',
    absent:  'Absen',
}

// ─── Refs ─────────────────────────────────────────────────────────────────────
const videoEl   = ref<HTMLVideoElement | null>(null)
const canvasEl  = ref<HTMLCanvasElement | null>(null)

const modelLoading   = ref(true)
const modelProgress  = ref('')
const camStatus      = ref('detecting')
const faceMsg        = ref('')
const faceMsgType    = ref('info')
const recognizedUser = ref<any>(null)
const attendanceMsg  = ref('')
const todayAttendance = ref<any>(null)

// Liveness
const livenessActive        = ref(false)
const livenessCompleted     = ref(false)
const challenges            = ref<any[]>([])
const currentChallengeIndex = ref(0)
const challengeFailed       = ref(false)
const timerPct              = ref(100)
const secsLeft              = ref(CHALLENGE_SECS)

const currentChallenge = computed(() => challenges.value[currentChallengeIndex.value] ?? null)

// Blink state (reactive untuk debug display)
const earBaselineReady         = ref(false)
const earBaselineFramesDisplay = ref(0)
const earScoreL                = ref(0)
const earScoreR                = ref(0)
const earBaseline              = ref(0)
const earCloseThresh           = ref(0)
const eyeOpen                  = ref(true)
const blinkCount               = ref(0)
const returnPhase              = ref(false)

// Internal mutable state
let _stream:         MediaStream | null = null
let _detectInterval: number | null      = null
let _timerInterval:  number | null      = null
let _isDetecting   = false
let _successLock   = false
let faceProfiles: any[] = []

// Blink internals
let _eyeWasOpen        = true
let _blinkFrames       = 0
let _blinkCooldown     = 0
let _earBaselineSum    = 0
let _earBaselineFrames = 0
let _earBaseline       = 0
let _blinkCount        = 0

// Movement internals
let _baseYaw   = 0
let _basePitch = 0
let _baseSet   = false
let _returnPhase = false

// Pending
let _pendingProfile: any = null
let _secsLeft = CHALLENGE_SECS

// ─── Computed ─────────────────────────────────────────────────────────────────
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

// ─── Models ───────────────────────────────────────────────────────────────────
let _modelsLoaded = false

async function loadModels() {
    if (_modelsLoaded) { modelLoading.value = false; return }
    modelLoading.value = true
    try {
        for (const [label, net] of [
            ['SSD Detector',  faceapi.nets.ssdMobilenetv1],
            ['Tiny Detector', faceapi.nets.tinyFaceDetector],
            ['Landmarks',     faceapi.nets.faceLandmark68Net],
            ['Recognition',   faceapi.nets.faceRecognitionNet],
        ] as [string, any][]) {
            modelProgress.value = `Memuat ${label}...`
            await net.loadFromUri(MODEL_URL)
        }
        _modelsLoaded = true
        modelProgress.value = 'Siap!'
    } catch (e) {
        console.error('Face model error:', e)
        faceMsg.value = 'Gagal memuat model AI. Refresh halaman.'
        faceMsgType.value = 'err'
    } finally {
        modelLoading.value = false
    }
}

async function loadProfiles() {
    try {
        const res = await axios.get('/face/profiles')
        faceProfiles = res.data.data ?? []
    } catch { faceProfiles = [] }
}

// ─── Attendance ───────────────────────────────────────────────────────────────
async function fetchToday() {
    try {
        const res = await axios.get('/attendance/today')
        todayAttendance.value = res.data.data ?? null
    } catch { todayAttendance.value = null }
}

// ─── Camera ───────────────────────────────────────────────────────────────────
async function startCamera() {
    try {
        _stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 480, height: 360, facingMode: 'user' }
        })
        if (!videoEl.value) return
        videoEl.value.srcObject = _stream
        await new Promise(res => {
            videoEl.value!.onloadedmetadata = () => res(null)
            setTimeout(res, 3000)
        })
        await videoEl.value.play()
        camStatus.value = 'detecting'
        startDetectLoop()
    } catch (e: any) {
        faceMsg.value = 'Gagal mengakses kamera: ' + (e.message ?? e)
        faceMsgType.value = 'err'
    }
}

function stopCamera() {
    if (_detectInterval) { clearInterval(_detectInterval); _detectInterval = null }
    stopTimer()
    _stream?.getTracks().forEach(t => t.stop())
    _stream = null
}

// ─── Detect Loop ──────────────────────────────────────────────────────────────
const SSD_OPTS  = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.2 })
const TINY_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.3 })

function startDetectLoop() {
    if (_detectInterval) clearInterval(_detectInterval)
    const ms = livenessActive.value ? 80 : 300
    _detectInterval = window.setInterval(async () => {
        if (document.hidden) return
        if (!videoEl.value || videoEl.value.readyState < 2 || _isDetecting || _successLock) return
        _isDetecting = true
        try { await detectFrame() } finally { _isDetecting = false }
    }, ms)
}

async function detectFrame() {
    if (!videoEl.value) return
    const withDesc = !livenessActive.value
    let det: any = null

    try {
        if (withDesc) {
            det = await faceapi.detectSingleFace(videoEl.value, SSD_OPTS).withFaceLandmarks().withFaceDescriptor()
            if (!det) det = await faceapi.detectSingleFace(videoEl.value, TINY_OPTS).withFaceLandmarks().withFaceDescriptor()
        } else {
            det = await faceapi.detectSingleFace(videoEl.value, TINY_OPTS).withFaceLandmarks()
            if (!det) det = await faceapi.detectSingleFace(videoEl.value, SSD_OPTS).withFaceLandmarks()
        }
    } catch { det = null }

    drawBox(det)

    if (!det) {
        if (!livenessActive.value && !_successLock) camStatus.value = 'no_face'
        return
    }

    // Liveness mode
    if (livenessActive.value && !livenessCompleted.value) {
        camStatus.value = 'liveness'
        processLiveness(det); return
    }

    // Recognition mode
    if (!livenessActive.value && !livenessCompleted.value && !_successLock) {
        if (!faceProfiles.length) { camStatus.value = 'no_face'; return }
        camStatus.value = 'ready'
        const desc: number[] = Array.from(det.descriptor as number[])
        let bestDist  = Infinity
        let bestMatch: any = null
        for (const p of faceProfiles) {
            const d = euclidean(desc, p.descriptor as number[])
            if (d < bestDist) { bestDist = d; bestMatch = p }
        }
        if (bestDist <= THRESHOLD && bestMatch) {
            startLiveness(bestMatch)
        }
    }
}

function drawBox(det: any) {
    if (!canvasEl.value || !videoEl.value) return
    canvasEl.value.width  = videoEl.value.videoWidth
    canvasEl.value.height = videoEl.value.videoHeight
    const ctx = canvasEl.value.getContext('2d')
    if (!ctx) return
    ctx.clearRect(0, 0, canvasEl.value.width, canvasEl.value.height)
    if (!det) return
    const box   = det.detection.box
    const color = camStatus.value === 'success' || camStatus.value === 'ready' ? '#17c653'
                : camStatus.value === 'liveness'      ? '#f6c000'
                : camStatus.value === 'liveness_fail' ? '#f64e60'
                : '#009ef7'
    ctx.strokeStyle = color; ctx.lineWidth = 3
    ctx.shadowColor = color; ctx.shadowBlur = 10
    ctx.strokeRect(box.x, box.y, box.width, box.height)
}

// ─── Liveness ────────────────────────────────────────────────────────────────
function startLiveness(profile: any) {
    _pendingProfile          = profile
    challenges.value         = pickChallenges(3)
    currentChallengeIndex.value = 0
    challengeFailed.value    = false
    livenessActive.value     = true
    livenessCompleted.value  = false
    camStatus.value          = 'liveness'
    resetMovement()
    startTimer()
    startDetectLoop()
}

function resetMovement() {
    _baseYaw = 0; _basePitch = 0; _baseSet = false
    _eyeWasOpen = true; eyeOpen.value = true
    _blinkFrames = 0; _blinkCooldown = 0
    _earBaselineSum = 0; _earBaselineFrames = 0; _earBaseline = 0
    earBaselineReady.value = false; earBaselineFramesDisplay.value = 0
    earScoreL.value = 0; earScoreR.value = 0
    earBaseline.value = 0; earCloseThresh.value = 0
    _blinkCount = 0; blinkCount.value = 0
    _returnPhase = false; returnPhase.value = false
}

function startTimer() {
    stopTimer()
    _secsLeft       = CHALLENGE_SECS
    secsLeft.value  = CHALLENGE_SECS
    timerPct.value  = 100
    _timerInterval  = window.setInterval(() => {
        if (document.hidden) return
        _secsLeft      -= 0.1
        secsLeft.value  = Math.max(0, _secsLeft)
        timerPct.value  = Math.max(0, (_secsLeft / CHALLENGE_SECS) * 100)
        if (_secsLeft <= 0) onFail()
    }, 100)
}

function stopTimer() {
    if (_timerInterval) { clearInterval(_timerInterval); _timerInterval = null }
}

function onSuccess() {
    stopTimer()
    const next = currentChallengeIndex.value + 1
    if (next >= challenges.value.length) {
        livenessCompleted.value = true
        livenessActive.value    = false
        _successLock            = true
        camStatus.value         = 'processing'
        doAttendance(_pendingProfile)
    } else {
        currentChallengeIndex.value = next
        resetMovement()
        startTimer()
    }
}

function onFail() {
    stopTimer()
    challengeFailed.value   = true
    livenessActive.value    = false
    livenessCompleted.value = false
    _pendingProfile         = null
    _successLock            = false
    camStatus.value         = 'liveness_fail'
    faceMsg.value           = 'Verifikasi gagal. Ikuti instruksi dengan benar lalu coba lagi.'
    faceMsgType.value       = 'err'
    setTimeout(() => { challengeFailed.value = false; faceMsg.value = ''; camStatus.value = 'detecting' }, 3000)
}

function processLiveness(det: any) {
    const pos = det.landmarks.positions
    const ch  = currentChallenge.value
    if (!ch || pos.length < 68) return

    // ── BLINK ────────────────────────────────────────────────────────────────
    if (ch.type === 'blink') {
        const faceW  = Math.abs(pos[16].x - pos[0].x)
        const sL     = eyeOpenScore(pos.slice(36, 42), faceW)
        const sR     = eyeOpenScore(pos.slice(42, 48), faceW)
        const score  = Math.min(sL, sR)
        const needDouble = !!ch.double

        earScoreL.value = sL; earScoreR.value = sR

        // Kumpulkan baseline
        if (_eyeWasOpen && _earBaselineFrames < 15 && score > 0.01 && score < 0.15) {
            _earBaselineSum += score; _earBaselineFrames++
            earBaselineFramesDisplay.value = _earBaselineFrames
            if (_earBaselineFrames >= 15) {
                _earBaseline = _earBaselineSum / _earBaselineFrames
                earBaselineReady.value = true
                earBaseline.value = _earBaseline
            }
        }

        const base      = earBaselineReady.value ? _earBaseline : 0.025
        const EOS_CLOSE = base * 0.50
        const EOS_OPEN  = base * 0.70
        earBaseline.value   = base
        earCloseThresh.value = EOS_CLOSE

        if (_blinkCooldown > 0) { _blinkCooldown--; return }

        if (!_eyeWasOpen) {
            _blinkFrames++
            if (score > EOS_OPEN) {
                if (_blinkFrames >= 1) {
                    _blinkCount++; blinkCount.value = _blinkCount
                    _eyeWasOpen = true; eyeOpen.value = true
                    _blinkFrames = 0; _blinkCooldown = 3
                    if (!needDouble || _blinkCount >= 2) {
                        _blinkCount = 0; blinkCount.value = 0
                        onSuccess(); return
                    }
                } else {
                    _eyeWasOpen = true; eyeOpen.value = true; _blinkFrames = 0
                }
            }
            if (_blinkFrames > 30) { _eyeWasOpen = true; eyeOpen.value = true; _blinkFrames = 0 }
        } else {
            eyeOpen.value = true
            if (score < EOS_CLOSE) { _eyeWasOpen = false; eyeOpen.value = false; _blinkFrames = 0 }
        }

    // ── TURN LEFT / RIGHT ────────────────────────────────────────────────────
    } else if (ch.type === 'turn_left' || ch.type === 'turn_right') {
        const noseX = pos[30].x, lx = pos[36].x, rx = pos[45].x
        const faceW = Math.abs(rx - lx)
        if (faceW < 1) return
        const yaw = (noseX - (lx + rx) / 2) / faceW
        if (!_baseSet) { _baseYaw = yaw; _baseSet = true }
        const delta = yaw - _baseYaw
        const THRESH = 0.06
        if (ch.returnToCenter) {
            if (!_returnPhase) {
                if (ch.type === 'turn_left' ? delta > THRESH : delta < -THRESH) { _returnPhase = true; returnPhase.value = true }
            } else {
                if (Math.abs(delta) < 0.04) { _returnPhase = false; returnPhase.value = false; onSuccess(); return }
            }
        } else {
            if (ch.type === 'turn_left'  && delta >  THRESH) { onSuccess(); return }
            if (ch.type === 'turn_right' && delta < -THRESH) { onSuccess(); return }
        }

    // ── NOD UP / DOWN ────────────────────────────────────────────────────────
    } else if (ch.type === 'nod_up' || ch.type === 'nod_down') {
        const noseY = pos[30].y
        const topY  = (pos[36].y + pos[45].y) / 2
        const chinY = pos[57]?.y ?? pos[8].y
        const faceH = Math.abs(chinY - topY)
        if (faceH < 1) return
        const pitch = (noseY - topY) / faceH
        if (!_baseSet) { _basePitch = pitch; _baseSet = true }
        const delta = pitch - _basePitch
        if (ch.type === 'nod_up'   && delta < -0.04) { onSuccess(); return }
        if (ch.type === 'nod_down' && delta >  0.04) { onSuccess(); return }
    }
}

// ─── Attendance API ───────────────────────────────────────────────────────────
async function doAttendance(profile: any) {
    try {
        const photo = videoEl.value ? capturePhoto(videoEl.value) : null

        // Login via face
        const loginRes = await axios.post('/face/login', { user_id: profile.user_id })
        recognizedUser.value = { name: profile.name }

        // Check-in atau check-out
        const today = todayAttendance.value
        if (!today?.check_in_time) {
            const res = await axios.post('/attendance/check-in', { photo })
            attendanceMsg.value = res.data?.message ?? 'Check-in berhasil'
        } else if (!today?.check_out_time) {
            const checkInTime = new Date(today.date + ' ' + today.check_in_time)
            const diffHours   = (Date.now() - checkInTime.getTime()) / 3600000
            if (diffHours >= 3) {
                const res = await axios.post('/attendance/check-out', { photo })
                attendanceMsg.value = res.data?.message ?? 'Check-out berhasil'
            } else {
                attendanceMsg.value = `Check-out tersedia ${Math.ceil(3 - diffHours)} jam lagi`
            }
        } else {
            attendanceMsg.value = 'Absensi hari ini sudah lengkap'
        }

        camStatus.value = 'success'
        await fetchToday()
        stopCamera()
    } catch (e: any) {
        faceMsg.value     = e.response?.data?.message ?? e.message ?? 'Gagal proses absensi'
        faceMsgType.value = 'err'
        _successLock      = false
        livenessCompleted.value = false
        livenessActive.value    = false
        _pendingProfile   = null
        camStatus.value   = 'detecting'
        setTimeout(() => { faceMsg.value = '' }, 4000)
    }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    await fetchToday()
    await loadModels()
    await loadProfiles()
    await new Promise(r => setTimeout(r, 100))
    await startCamera()
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
.cam-wrap--ready,
.cam-wrap--success    { border-color: #17c653; box-shadow: 0 0 20px rgba(23,198,83,.2); }
.cam-wrap--liveness   { border-color: #f6c000; box-shadow: 0 0 20px rgba(246,192,0,.2); }
.cam-wrap--failed,
.cam-wrap--liveness_fail { border-color: #f64e60; box-shadow: 0 0 16px rgba(246,78,96,.2); }
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
.cam-pill--ready      .cam-pill__dot,
.cam-pill--success    .cam-pill__dot { background: #17c653; box-shadow: 0 0 5px #17c653; animation: blink .9s ease-in-out infinite; }
.cam-pill--liveness   .cam-pill__dot { background: #f6c000; box-shadow: 0 0 5px #f6c000; animation: blink .7s ease-in-out infinite; }
.cam-pill--failed     .cam-pill__dot,
.cam-pill--liveness_fail .cam-pill__dot { background: #f64e60; }
.cam-pill--processing .cam-pill__dot { background: #009ef7; animation: blink .5s ease-in-out infinite; }
.cam-pill--ready      { color: #17c653; }
.cam-pill--success    { color: #17c653; }
.cam-pill--failed,
.cam-pill--liveness_fail { color: #f64e60; }
.cam-pill--processing { color: #009ef7; }
.cam-pill--liveness   { color: #f6c000; }

@keyframes blink { 0%,100% { opacity: 1 } 50% { opacity: .3 } }

/* Liveness dot progress */
.lv-dot { transition: background-color .3s; }
.h-8px   { height: 8px !important; }
</style>