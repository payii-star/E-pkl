<template>
    <div class="row g-5">
        <div class="col-12 col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold">Absen Masuk</h2>
                    </div>
                    <div class="card-toolbar">
                        <span class="badge" :class="statusBadgeClass">{{ statusLabel }}</span>
                    </div>
                </div>
                <div class="card-body pt-2 d-flex flex-column align-items-center">

                    <!-- Masih memuat status hari ini -->
                    <div v-if="loading" class="w-100 text-center py-10">
                        <span class="spinner-border text-primary"></span>
                        <div class="text-muted fs-7 mt-3">Memuat status absensi...</div>
                    </div>

                    <!-- Sudah absen masuk hari ini -->
                    <div v-else-if="alreadyDone" class="w-100 text-center py-10">
                        <div class="symbol symbol-60px mb-3 mx-auto">
                            <span class="symbol-label bg-light-success">
                                <KTIcon icon-name="check-circle" icon-class="fs-1 text-success" />
                            </span>
                        </div>
                        <div class="fw-bold fs-5 text-gray-800">Kamu sudah absen masuk hari ini</div>
                        <div class="text-muted fs-7 mt-1">Selamat bekerja!</div>
                        <div class="text-muted fs-7 mt-3">
                            Absen masuk tercatat jam <span class="fw-semibold text-gray-700">{{ todayAttendance?.check_in_time ?? '-' }}</span>
                        </div>
                    </div>

                    <!-- Hari ini libur: kamera tidak perlu dibuka sama sekali -->
                    <div v-else-if="!isWorkingDay" class="w-100 text-center py-10">
                        <div class="symbol symbol-60px mb-3 mx-auto">
                            <span class="symbol-label bg-light-danger">
                                <KTIcon icon-name="calendar-remove" icon-class="fs-1 text-danger" />
                            </span>
                        </div>
                        <div class="fw-bold fs-5 text-gray-800 mb-1">
                            {{ isHoliday ? 'Hari ini tanggal merah' : 'Hari ini bukan hari kerja' }}
                        </div>
                        <div v-if="holidayLabel" class="text-danger fw-semibold fs-6 mb-2">{{ holidayLabel }}</div>
                        <div class="text-muted fs-7">{{ offReason ?? 'Absensi tidak tersedia pada hari libur.' }}</div>
                        <div class="text-muted fs-8 mt-4">Silakan kembali pada hari kerja berikutnya sesuai jadwalmu.</div>
                    </div>

                    <!-- Kamera belum dibuka: tampilkan tombol -->
                    <div v-else-if="!cameraOpen" class="w-100 text-center py-10">
                        <div class="symbol symbol-60px mb-3 mx-auto">
                            <span class="symbol-label bg-light-primary">
                                <KTIcon icon-name="camera" icon-class="fs-1 text-primary" />
                            </span>
                        </div>
                        <div class="fw-bold fs-5 text-gray-800 mb-1">Siap absen masuk?</div>
                        <div class="text-muted fs-7 mb-2">Kamera akan menyala setelah kamu menekan tombol di bawah</div>
                        <div v-if="workHours" class="text-muted fs-8 mb-5">
                            Jam kerja hari ini
                            <span class="fw-semibold text-gray-700">{{ shortTime(workHours.start) }} - {{ shortTime(workHours.end) }}</span>
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

                        <!-- Tombol konfirmasi / ambil ulang -->
                        <div v-else class="d-flex gap-2 mb-3">
                            <button class="btn btn-success" :disabled="submitting" @click="submitCheckIn">
                                <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                                <KTIcon v-else icon-name="check" icon-class="fs-4 me-2" />
                                Konfirmasi Absen Masuk
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

const cameraOpen      = ref(false)
const capturedPhoto   = ref<string | null>(null)
const submitting      = ref(false)
const loading         = ref(true)
const errMsg          = ref('')
const todayAttendance = ref<any>(null)

// Status hari kerja — dikirim backend lewat GET /attendances/today.
// Default true supaya kalau request gagal, halaman tidak salah menampilkan
// "libur" padahal sebenarnya hari kerja (backend tetap jadi penjaga akhir).
const isWorkingDay  = ref(true)
const isHoliday     = ref(false)
const holidayLabel  = ref<string | null>(null)
const offReason     = ref<string | null>(null)
const workHours     = ref<{ start: string; end: string } | null>(null)

let _stream: MediaStream | null = null

// ─── Computed ─────────────────────────────────────────────────────────────────
const alreadyDone = computed(() =>
    !!todayAttendance.value?.check_in_time
)

const statusLabel = computed(() => {
    if (!isWorkingDay.value && !todayAttendance.value?.check_in_time) return 'Libur'
    if (!todayAttendance.value?.check_in_time) return 'Belum Absen'
    return 'Sudah Masuk'
})

const statusBadgeClass = computed(() => {
    if (!isWorkingDay.value && !todayAttendance.value?.check_in_time) return 'badge-light-danger'
    if (!todayAttendance.value?.check_in_time) return 'badge-light-warning'
    return 'badge-light-success'
})

// ─── Helpers ──────────────────────────────────────────────────────────────────
function shortTime(value?: string | null) {
    if (!value) return '-'
    return value.slice(0, 5) // "08:00:00" -> "08:00"
}

// ─── Data loading ─────────────────────────────────────────────────────────────
async function fetchToday() {
    try {
        const res = await axios.get('/attendances/today')
        todayAttendance.value = res.data.data ?? null
        isWorkingDay.value = res.data.is_working_day ?? true
        isHoliday.value = res.data.is_holiday ?? false
        holidayLabel.value = res.data.holiday_label ?? null
        offReason.value = res.data.off_reason ?? null
        workHours.value = res.data.work_hours ?? null
    } catch (e) {
        console.error('Gagal ambil data absensi hari ini:', e)
    } finally {
        loading.value = false
    }
}

// ─── Camera ───────────────────────────────────────────────────────────────────
async function openCamera() {
    errMsg.value = ''
    capturedPhoto.value = null
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
}

// ─── Check-in ─────────────────────────────────────────────────────────────────
async function submitCheckIn() {
    if (!capturedPhoto.value || submitting.value) return
    submitting.value = true
    errMsg.value = ''
    try {
        await axios.post('/attendances/check-in-web', { photo: capturedPhoto.value })
        stopCamera()
        cameraOpen.value = false
        capturedPhoto.value = null
        await fetchToday()
    } catch (e: any) {
        errMsg.value = e.response?.data?.message ?? e.message ?? 'Gagal mencatat absen masuk'
        // Kalau ditolak karena hari libur (403 dari backend), tutup kamera dan
        // refresh status supaya halaman langsung pindah ke tampilan libur.
        if (e.response?.status === 403) {
            stopCamera()
            cameraOpen.value = false
            capturedPhoto.value = null
            await fetchToday()
        }
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