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
                        <button class="btn btn-primary" @click="openCamera">
                            <KTIcon icon-name="camera" icon-class="fs-4 me-2" />
                            Buka Kamera
                        </button>
                        <div v-if="errMsg" class="alert alert-danger w-100 py-2 fs-7 mt-5">
                            {{ errMsg }}
                        </div>
                    </div>

                    <template v-else>
                        <!-- Kamera hidup, belum ambil foto -->
                        <div v-if="!capturedPhoto" class="cam-wrap position-relative w-100 mb-4">
                            <video ref="videoEl" autoplay muted playsinline class="cam-video rounded" />
                        </div>

                        <!-- Sudah ambil foto: tampilkan preview -->
                        <div v-else class="cam-wrap position-relative w-100 mb-4">
                            <img :src="capturedPhoto" class="cam-photo rounded" alt="Preview foto absen" />
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

                        <!-- Tombol ambil foto -->
                        <div v-if="!capturedPhoto" class="d-flex gap-2 mb-3">
                            <button class="btn btn-primary" @click="takePhoto">
                                <KTIcon icon-name="camera" icon-class="fs-4 me-2" />
                                Ambil Foto
                            </button>
                            <button class="btn btn-light" @click="closeCamera">
                                <KTIcon icon-name="cross" icon-class="fs-6 me-1" />
                                Tutup Kamera
                            </button>
                        </div>

                        <!-- Konfirmasi pulang lebih awal -->
                        <div v-else-if="isEarlyLeave && !earlyLeaveConfirmed" class="w-100 mb-3">
                            <div class="alert alert-warning py-3 fs-7 mb-3 text-start">
                                <div class="fw-bold mb-1">Apakah Anda yakin pulang saat ini?</div>
                                <div>Jam pulang resmi adalah 16:00. Saat ini Anda pulang lebih awal <span class="fw-bold">{{ earlyLeaveDisplay }}</span> jam.</div>
                            </div>
                            <div class="d-flex gap-2 justify-content-center">
                                <button class="btn btn-warning" :disabled="submitting" @click="confirmEarlyLeave">
                                    Yakin Pulang Saat Ini
                                </button>
                                <button class="btn btn-light" :disabled="submitting" @click="cancelEarlyLeave">
                                    Batal
                                </button>
                            </div>
                        </div>

                        <!-- Tombol konfirmasi / ambil ulang -->
                        <div v-else class="d-flex gap-2 mb-3">
                            <button class="btn btn-success" :disabled="submitting" @click="submitCheckOut">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                <KTIcon v-else icon-name="check" icon-class="fs-4 me-2" />
                                Konfirmasi Absen Pulang
                            </button>
                            <button class="btn btn-light" :disabled="submitting" @click="retakePhoto">
                                <KTIcon icon-name="arrows-circle" icon-class="fs-6 me-1" />
                                Ambil Ulang
                            </button>
                        </div>

                        <div v-if="errMsg" class="alert alert-danger w-100 py-2 fs-7">
                            {{ errMsg }}
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Canvas tersembunyi buat capture foto dari video -->
        <canvas ref="canvasEl" style="display:none"></canvas>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from '@/libs/axios'

// Sebagian webcam/driver (terutama di Windows) mengirim stream mentah yang SUDAH
// ter-mirror duluan, sebelum disentuh CSS/canvas sama sekali. Kalau foto hasil
// capture masih kebalik/mirror padahal canvas sudah digambar apa adanya, berarti
// stream mentahnya sendiri yang mirror — makanya di-flip lagi di sini untuk
// membatalkan mirror bawaan tsb. Kalau di device kamu ternyata malah jadi kebalik
// SETELAH ini, tinggal ubah true jadi false.
const FLIP_CAPTURE = true

// ─── Refs ─────────────────────────────────────────────────────────────────────
const videoEl  = ref<HTMLVideoElement | null>(null)
const canvasEl = ref<HTMLCanvasElement | null>(null)

const cameraOpen         = ref(false)
const capturedPhoto      = ref<string | null>(null)
const submitting         = ref(false)
const errMsg             = ref('')
const todayAttendance    = ref<any>(null)
const earlyLeaveConfirmed = ref(false)

let _stream: MediaStream | null = null

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

const isEarlyLeave = computed(() => {
    const now = new Date()
    const official = new Date(now)
    official.setHours(16, 0, 0, 0)
    return now.getTime() < official.getTime()
})

const earlyLeaveMinutes = computed(() => {
    if (!isEarlyLeave.value) return 0
    const now = new Date()
    const official = new Date(now)
    official.setHours(16, 0, 0, 0)
    return Math.max(0, Math.round((official.getTime() - now.getTime()) / 60000))
})

const earlyLeaveDisplay = computed(() => {
    const total = earlyLeaveMinutes.value
    const hours = Math.floor(total / 60)
    const minutes = total % 60
    return `-${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
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

// ─── Camera ───────────────────────────────────────────────────────────────────
async function openCamera() {
    errMsg.value = ''
    capturedPhoto.value = null
    earlyLeaveConfirmed.value = false
    try {
        _stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 480, height: 360, facingMode: 'user' },
        })
        cameraOpen.value = true
        await new Promise((resolve) => setTimeout(resolve, 0)) // tunggu video element ke-render
        if (videoEl.value) {
            videoEl.value.srcObject = _stream
            await videoEl.value.play()
        }
    } catch (e) {
        console.error('Gagal akses kamera:', e)
        errMsg.value = 'Tidak bisa mengakses kamera. Pastikan izin kamera diaktifkan di browser.'
    }
}

function stopCamera() {
    _stream?.getTracks().forEach((t) => t.stop())
    _stream = null
}

function closeCamera() {
    stopCamera()
    cameraOpen.value = false
    capturedPhoto.value = null
    earlyLeaveConfirmed.value = false
    errMsg.value = ''
}

function takePhoto() {
    if (!videoEl.value || !canvasEl.value) return
    const video = videoEl.value
    const canvas = canvasEl.value
    canvas.width = video.videoWidth || 480
    canvas.height = video.videoHeight || 360
    const ctx = canvas.getContext('2d')
    if (!ctx) return
    if (FLIP_CAPTURE) {
        ctx.translate(canvas.width, 0)
        ctx.scale(-1, 1)
    }
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
    capturedPhoto.value = canvas.toDataURL('image/jpeg', 0.85)
}

function retakePhoto() {
    capturedPhoto.value = null
    earlyLeaveConfirmed.value = false
    errMsg.value = ''
}

function confirmEarlyLeave() {
    earlyLeaveConfirmed.value = true
    errMsg.value = ''
}

function cancelEarlyLeave() {
    earlyLeaveConfirmed.value = false
    errMsg.value = ''
}

// ─── Check-out ────────────────────────────────────────────────────────────────
async function submitCheckOut() {
    if (!capturedPhoto.value || submitting.value) return
    if (isEarlyLeave.value && !earlyLeaveConfirmed.value) {
        errMsg.value = 'Anda pulang lebih awal dari jam resmi 16:00. Silakan konfirmasi dulu untuk melanjutkan.'
        return
    }

    submitting.value = true
    errMsg.value = ''
    try {
        await axios.post('/attendances/check-out', { photo: capturedPhoto.value })
        stopCamera()
        cameraOpen.value = false
        capturedPhoto.value = null
        earlyLeaveConfirmed.value = false
        await fetchToday()
    } catch (e: any) {
        errMsg.value = e.response?.data?.message ?? e.message ?? 'Gagal mencatat absen pulang'
    } finally {
        submitting.value = false
    }
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
.cam-wrap {
    aspect-ratio: 4/3;
    background: #0d0f14;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e4e6ef;
}
.cam-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1);
    display: block;
}
.cam-photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
</style>