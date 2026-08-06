<template>
    <div class="row g-5">

        <!-- ══ DAFTAR INTERN ══ -->
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Daftar Peserta Magang</h2>
                    </div>
                    <div class="card-toolbar">
                        <div class="position-relative w-200px">
                            <KTIcon icon-name="magnifier"
                                icon-class="fs-3 text-gray-500 position-absolute top-50 translate-middle-y ms-3" />
                            <input v-model="search" type="text" class="form-control form-control-sm ps-10"
                                placeholder="Cari nama..." />
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div v-if="loadingInterns" class="text-center py-10">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div v-else-if="filteredInterns.length === 0" class="text-center text-muted py-8">
                        Tidak ada data peserta magang
                    </div>
                    <div v-else class="d-flex flex-column gap-3">
                        <div v-for="intern in filteredInterns" :key="intern.intern_id"
                            class="intern-card d-flex align-items-center gap-3 p-3 rounded cursor-pointer"
                            :class="{ 'intern-card--active': selected?.intern_id === intern.intern_id }"
                            @click="selectIntern(intern)">

                            <!-- Avatar -->
                            <div class="symbol symbol-45px flex-shrink-0">
                                <img v-if="intern.photo" :src="intern.photo" alt="foto" class="rounded" />
                                <span v-else class="symbol-label bg-light-primary text-primary fw-bold fs-5">
                                    {{ intern.name?.charAt(0)?.toUpperCase() }}
                                </span>
                            </div>

                            <!-- Info -->
                            <div class="flex-fill min-w-0">
                                <div class="fw-bold text-gray-800 text-truncate">{{ intern.name }}</div>
                                <div class="text-muted fs-8 text-truncate">{{ intern.institusi_asal ?? '-' }}</div>
                            </div>

                            <!-- Status wajah -->
                            <div class="flex-shrink-0">
                                <span v-if="intern.has_face_profile"
                                    class="badge badge-light-success">
                                    <KTIcon icon-name="check-circle" icon-class="fs-7 me-1" />
                                    Terdaftar
                                </span>
                                <span v-else class="badge badge-light-warning">
                                    Belum
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ PANEL KANAN ══ -->
        <div class="col-12 col-xl-7">

            <!-- Placeholder jika belum pilih -->
            <div v-if="!selected" class="card h-100 d-flex align-items-center justify-content-center">
                <div class="text-center text-muted py-10">
                    <KTIcon icon-name="people" icon-class="fs-3x mb-3 text-gray-300" />
                    <div class="fs-5 fw-semibold">Pilih peserta magang</div>
                    <div class="fs-7 mt-1">Klik nama di sebelah kiri untuk mengelola face profile</div>
                </div>
            </div>

            <template v-else>
                <!-- Info intern terpilih -->
                <div class="card mb-5">
                    <div class="card-body py-4">
                        <div class="d-flex align-items-center gap-4">
                            <div class="symbol symbol-60px">
                                <img v-if="selected.photo" :src="selected.photo" class="rounded" />
                                <span v-else class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                                    {{ selected.name?.charAt(0)?.toUpperCase() }}
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="fw-bold fs-4 text-gray-800">{{ selected.name }}</div>
                                <div class="text-muted fs-7">{{ selected.email }}</div>
                                <div class="text-muted fs-7">{{ selected.institusi_asal }} · {{ selected.start_date }} s/d {{ selected.end_date }}</div>
                            </div>
                            <!-- Tombol impersonate / login sebagai intern -->
                            <button class="btn btn-sm btn-light-info" :disabled="impersonating"
                                @click="doImpersonate" title="Login sebagai intern ini">
                                <span v-if="impersonating" class="spinner-border spinner-border-sm me-1"></span>
                                <KTIcon v-else icon-name="entrance-right" icon-class="fs-5 me-1" />
                                Login sebagai
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Daftarkan Wajah -->
                <div class="card">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <KTIcon icon-name="eye" icon-class="fs-2 text-primary me-2" />
                            <h3 class="fw-bold mb-0">
                                {{ selected.has_face_profile ? 'Update Face Profile' : 'Daftarkan Wajah' }}
                            </h3>
                        </div>
                        <div class="card-toolbar">
                            <button v-if="selected.has_face_profile"
                                class="btn btn-sm btn-light-danger"
                                :disabled="deletingFace" @click="deleteFaceProfile">
                                <span v-if="deletingFace" class="spinner-border spinner-border-sm me-1"></span>
                                <KTIcon v-else icon-name="trash" icon-class="fs-6 me-1" />
                                Hapus Wajah
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Preview wajah terdaftar -->
                        <div v-if="selected.face_photo" class="mb-5 text-center">
                            <div class="text-muted fs-7 mb-2">Foto wajah tersimpan:</div>
                            <img :src="selected.face_photo" alt="face"
                                class="rounded" style="width:120px;height:120px;object-fit:cover;border:2px solid #e4e6ef;" />
                        </div>

                        <!-- Loading model -->
                        <div v-if="modelLoading" class="text-center py-6">
                            <div class="spinner-border text-primary mb-3"></div>
                            <div class="text-muted fs-7">Memuat AI model... {{ modelProgress }}</div>
                        </div>

                        <template v-else>
                            <!-- Kamera -->
                            <div class="cam-wrap position-relative mb-4" :class="'cam-wrap--' + camStatus">
                                <video ref="videoEl" autoplay muted playsinline class="cam-video rounded w-100" />
                                <canvas ref="canvasEl" class="cam-canvas position-absolute top-0 start-0 w-100 h-100" />

                                <!-- Status pill -->
                                <div class="cam-pill" :class="'cam-pill--' + camStatus">
                                    <span class="cam-pill__dot"></span>
                                    <span v-if="camStatus === 'idle'">Kamera siap</span>
                                    <span v-else-if="camStatus === 'detecting'">Mendeteksi wajah...</span>
                                    <span v-else-if="camStatus === 'ready'">Wajah terdeteksi!</span>
                                    <span v-else-if="camStatus === 'no_face'">Arahkan wajah ke kamera</span>
                                    <span v-else-if="camStatus === 'capturing'">Mengambil data wajah...</span>
                                    <span v-else-if="camStatus === 'success'">Berhasil didaftarkan!</span>
                                </div>
                            </div>

                            <!-- Instruksi -->
                            <div class="notice d-flex bg-light-primary rounded p-4 mb-5">
                                <KTIcon icon-name="information" icon-class="fs-2 text-primary me-3 mt-1 flex-shrink-0" />
                                <div class="fs-7 text-gray-700">
                                    Pastikan wajah <b>{{ selected.name }}</b> terlihat jelas di kamera, pencahayaan cukup,
                                    dan wajah menghadap lurus ke depan. Klik <b>Daftarkan Wajah</b> saat status menunjukkan
                                    "Wajah terdeteksi!".
                                </div>
                            </div>

                            <!-- Pesan -->
                            <div v-if="registerMsg" class="alert py-3 fs-7 mb-4"
                                :class="registerMsgType === 'err' ? 'alert-danger' : 'alert-success'">
                                {{ registerMsg }}
                            </div>

                            <!-- Tombol daftar -->
                            <button class="btn btn-primary w-100"
                                :disabled="camStatus !== 'ready' || registering"
                                @click="registerFace">
                                <span v-if="registering" class="spinner-border spinner-border-sm me-2"></span>
                                <KTIcon v-else icon-name="fingerprint-scanning" icon-class="fs-4 me-2" />
                                {{ selected.has_face_profile ? 'Update Wajah' : 'Daftarkan Wajah' }}
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal impersonate berhasil -->
    <div v-if="impersonateToken" class="modal fade show d-block" tabindex="-1"
        style="background:rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Login Berhasil</h5>
                </div>
                <div class="modal-body text-center">
                    <div class="symbol symbol-60px mb-3 mx-auto">
                        <span class="symbol-label bg-light-success">
                            <KTIcon icon-name="check" icon-class="fs-1 text-success" />
                        </span>
                    </div>
                    <div class="fw-bold fs-5 mb-1">{{ selected?.name }}</div>
                    <div class="text-muted fs-7 mb-4">Token berhasil digenerate</div>

                    <div class="bg-light rounded p-3 text-start mb-4">
                        <div class="text-muted fs-8 mb-1">JWT Token:</div>
                        <div class="text-break fs-8 text-dark" style="word-break:break-all;">
                            {{ impersonateToken.slice(0, 60) }}...
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 fs-8 text-start">
                        Token ini bisa dipakai untuk testing API sebagai {{ selected?.name }}.
                        Jangan bagikan ke pihak lain.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button class="btn btn-sm btn-light-primary flex-fill" @click="copyToken">
                        <KTIcon icon-name="copy" icon-class="fs-6 me-1" />
                        Copy Token
                    </button>
                    <button class="btn btn-sm btn-primary flex-fill" @click="loginAsIntern">
                        <KTIcon icon-name="entrance-right" icon-class="fs-6 me-1" />
                        Masuk Sekarang
                    </button>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button class="btn btn-sm btn-light w-100" @click="impersonateToken = ''">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import * as faceapi from 'face-api.js'
import axios from '@/libs/axios'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router    = useRouter()

// ─── Constants ────────────────────────────────────────────────────────────────
const MODEL_URL = '/models'

// ─── State ────────────────────────────────────────────────────────────────────
const interns       = ref<any[]>([])
const selected      = ref<any>(null)
const search        = ref('')
const loadingInterns = ref(false)

// Camera
const videoEl  = ref<HTMLVideoElement | null>(null)
const canvasEl = ref<HTMLCanvasElement | null>(null)
const camStatus = ref('idle')

// Model
const modelLoading  = ref(false)
const modelProgress = ref('')
let _modelsLoaded = false

// Register
const registering    = ref(false)
const registerMsg    = ref('')
const registerMsgType = ref('ok')
const deletingFace   = ref(false)

// Impersonate
const impersonating    = ref(false)
const impersonateToken = ref('')
const impersonateUser  = ref<any>(null)

// Camera internals
let _stream:         MediaStream | null = null
let _detectInterval: number | null      = null
let _isDetecting   = false
let _lastDescriptor: number[] | null    = null
let _lastPhoto:      string | null      = null

// ─── Computed ─────────────────────────────────────────────────────────────────
const filteredInterns = computed(() =>
    interns.value.filter(i =>
        i.name?.toLowerCase().includes(search.value.toLowerCase()) ||
        i.institusi_asal?.toLowerCase().includes(search.value.toLowerCase())
    )
)

// ─── Load data ────────────────────────────────────────────────────────────────
async function loadInterns() {
    loadingInterns.value = true
    try {
        const res = await axios.get('/admin/face/interns')
        interns.value = res.data.data ?? []
    } catch (e: any) {
        console.error('Gagal load interns:', e)
    } finally {
        loadingInterns.value = false
    }
}

// ─── Select intern ────────────────────────────────────────────────────────────
async function selectIntern(intern: any) {
    selected.value      = intern
    registerMsg.value   = ''
    impersonateToken.value = ''
    _lastDescriptor     = null
    _lastPhoto          = null
    camStatus.value     = 'idle'

    // Start camera & load models
    stopCamera()
    await loadModels()
    await startCamera()
}

// ─── Face API Models ──────────────────────────────────────────────────────────
async function loadModels() {
    if (_modelsLoaded) return
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
    } catch (e) {
        console.error('Model error:', e)
    } finally {
        modelLoading.value = false
    }
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
        registerMsg.value     = 'Gagal mengakses kamera: ' + (e.message ?? e)
        registerMsgType.value = 'err'
    }
}

function stopCamera() {
    if (_detectInterval) { clearInterval(_detectInterval); _detectInterval = null }
    _stream?.getTracks().forEach(t => t.stop())
    _stream = null
}

// ─── Detect Loop ──────────────────────────────────────────────────────────────
const TINY_OPTS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 })
const SSD_OPTS  = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 })

function startDetectLoop() {
    if (_detectInterval) clearInterval(_detectInterval)
    _detectInterval = window.setInterval(async () => {
        if (!videoEl.value || videoEl.value.readyState < 2 || _isDetecting) return
        _isDetecting = true
        try { await detectFrame() } finally { _isDetecting = false }
    }, 400)
}

async function detectFrame() {
    if (!videoEl.value) return
    let det: any = null
    try {
        det = await faceapi.detectSingleFace(videoEl.value, TINY_OPTS)
            .withFaceLandmarks().withFaceDescriptor()
        if (!det) {
            det = await faceapi.detectSingleFace(videoEl.value, SSD_OPTS)
                .withFaceLandmarks().withFaceDescriptor()
        }
    } catch { det = null }

    drawBox(det)

    if (det) {
        camStatus.value  = 'ready'
        _lastDescriptor  = Array.from(det.descriptor)
        _lastPhoto       = capturePhoto()
    } else {
        camStatus.value  = 'no_face'
        _lastDescriptor  = null
        _lastPhoto       = null
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
    const box = det.detection.box
    ctx.strokeStyle = camStatus.value === 'ready' ? '#17c653' : '#009ef7'
    ctx.lineWidth   = 3
    ctx.shadowColor = ctx.strokeStyle
    ctx.shadowBlur  = 10
    ctx.strokeRect(box.x, box.y, box.width, box.height)
}

function capturePhoto(): string | null {
    if (!videoEl.value) return null
    try {
        const c = document.createElement('canvas')
        c.width = videoEl.value.videoWidth
        c.height = videoEl.value.videoHeight
        const ctx = c.getContext('2d')
        if (!ctx) return null
        ctx.translate(c.width, 0); ctx.scale(-1, 1)
        ctx.drawImage(videoEl.value, 0, 0, c.width, c.height)
        return c.toDataURL('image/jpeg', 0.85)
    } catch { return null }
}

// ─── Register Face ────────────────────────────────────────────────────────────
async function registerFace() {
    if (!_lastDescriptor || !selected.value) return
    registering.value   = true
    registerMsg.value   = ''
    camStatus.value     = 'capturing'

    try {
        await axios.post(`/admin/face/register/${selected.value.intern_id}`, {
            descriptor: _lastDescriptor,
            photo:      _lastPhoto,
        })

        registerMsg.value     = `Wajah ${selected.value.name} berhasil didaftarkan!`
        registerMsgType.value = 'ok'
        camStatus.value       = 'success'

        // Update status di list
        const idx = interns.value.findIndex(i => i.intern_id === selected.value.intern_id)
        if (idx >= 0) interns.value[idx].has_face_profile = true

        // Reload data intern terpilih
        await loadInterns()
        selected.value = interns.value.find(i => i.intern_id === selected.value.intern_id) ?? selected.value

        setTimeout(() => { camStatus.value = 'detecting' }, 2000)
    } catch (e: any) {
        registerMsg.value     = e.response?.data?.message ?? 'Gagal mendaftarkan wajah'
        registerMsgType.value = 'err'
        camStatus.value       = 'detecting'
    } finally {
        registering.value = false
    }
}

// ─── Delete Face Profile ──────────────────────────────────────────────────────
async function deleteFaceProfile() {
    if (!selected.value) return
    if (!confirm(`Hapus face profile ${selected.value.name}?`)) return

    deletingFace.value = true
    try {
        await axios.delete(`/admin/face/${selected.value.intern_id}`)
        registerMsg.value     = 'Face profile berhasil dihapus'
        registerMsgType.value = 'ok'

        await loadInterns()
        selected.value = interns.value.find(i => i.intern_id === selected.value.intern_id) ?? selected.value
    } catch (e: any) {
        registerMsg.value     = e.response?.data?.message ?? 'Gagal menghapus face profile'
        registerMsgType.value = 'err'
    } finally {
        deletingFace.value = false
    }
}

// ─── Impersonate ──────────────────────────────────────────────────────────────
async function doImpersonate() {
    if (!selected.value) return
    impersonating.value = true
    try {
        const res = await axios.post(`/admin/face/impersonate/${selected.value.intern_id}`)
        impersonateToken.value = res.data.token
        impersonateUser.value  = res.data.user
    } catch (e: any) {
        alert(e.response?.data?.message ?? 'Gagal login sebagai intern')
    } finally {
        impersonating.value = false
    }
}

function copyToken() {
    navigator.clipboard.writeText(impersonateToken.value)
        .then(() => alert('Token berhasil disalin!'))
        .catch(() => alert('Gagal salin token'))
}

function loginAsIntern() {
    if (!impersonateUser.value || !impersonateToken.value) return
    // Simpan token ke auth store lalu redirect ke dashboard
    authStore.setAuth({
        user:  impersonateUser.value,
        token: impersonateToken.value,
    } as any)
    impersonateToken.value = ''
    router.push({ name: 'dashboard' })
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    await loadInterns()
    await loadModels()
})

onUnmounted(() => {
    stopCamera()
})

// Reset kamera saat ganti intern
watch(selected, () => {
    registerMsg.value = ''
    camStatus.value   = 'idle'
})
</script>

<style scoped>
/* Intern card */
.intern-card {
    border: 1.5px solid #f1f1f2;
    transition: all .15s;
    cursor: pointer;
}
.intern-card:hover        { background: #f9f9f9; border-color: #d9d9e0; }
.intern-card--active      { background: #eef6ff; border-color: #009ef7 !important; }

/* Camera */
.cam-wrap {
    aspect-ratio: 4/3;
    background: #0d0f14;
    border-radius: 10px;
    overflow: hidden;
    border: 2px solid #e4e6ef;
    transition: border-color .3s, box-shadow .3s;
}
.cam-wrap--ready    { border-color: #17c653; box-shadow: 0 0 16px rgba(23,198,83,.2); }
.cam-wrap--success  { border-color: #17c653; box-shadow: 0 0 20px rgba(23,198,83,.3); }
.cam-wrap--no_face  { border-color: #f6c000; }
.cam-wrap--capturing { border-color: #009ef7; box-shadow: 0 0 16px rgba(0,158,247,.2); }

.cam-video  { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); display: block; }
.cam-canvas { transform: scaleX(-1); pointer-events: none; }

/* Status pill */
.cam-pill {
    position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);
    display: flex; align-items: center; gap: 6px;
    background: rgba(0,0,0,.7); backdrop-filter: blur(6px);
    border-radius: 20px; padding: 5px 14px;
    font-size: 12px; font-weight: 600; color: #cdcdde;
    white-space: nowrap; z-index: 5;
    border: 1px solid rgba(255,255,255,.1);
}
.cam-pill__dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #5e6278; flex-shrink: 0;
}
.cam-pill--ready   .cam-pill__dot,
.cam-pill--success .cam-pill__dot { background: #17c653; box-shadow: 0 0 5px #17c653; animation: blink .9s ease-in-out infinite; }
.cam-pill--no_face .cam-pill__dot { background: #f6c000; }
.cam-pill--capturing .cam-pill__dot { background: #009ef7; animation: blink .5s ease-in-out infinite; }
.cam-pill--ready   { color: #17c653; }
.cam-pill--success { color: #17c653; }
.cam-pill--no_face { color: #f6c000; }
.cam-pill--capturing { color: #009ef7; }

@keyframes blink { 0%,100% { opacity: 1 } 50% { opacity: .3 } }
</style>