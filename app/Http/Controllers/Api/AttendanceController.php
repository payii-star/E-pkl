<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Support\WorkScheduleResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    // GET /attendances
    public function index(Request $request)
    {
        $user = $request->user();
        $dateColumn = Attendance::dateColumn();

        $attendances = Attendance::where('user_id', $user->id)
            ->latest($dateColumn)
            ->get();

        return response()->json(['data' => $attendances]);
    }

    // GET /attendances/today
    // Dibikin terpisah dari index() supaya "hari ini" ditentukan oleh backend
    // (pakai timezone Asia/Jakarta dari config), bukan dicocokkan manual di
    // frontend — karena cast 'date' di model bisa bergeser kalau frontend
    // memakai timezone browser yang berbeda.
    //
    // Selain data absensi, response juga membawa status hari kerja
    // (is_working_day / off_reason / work_hours) supaya halaman Absen Masuk
    // & Absen Pulang bisa menampilkan pesan libur TANPA perlu membuka kamera
    // dulu. Backend tetap jadi sumber kebenaran: pengecekan yang sama juga
    // dijalankan ulang di endpoint check-in/check-out di bawah.
    public function today(Request $request)
    {
        $user = $request->user();
        $dateColumn = Attendance::dateColumn();
        $now = $this->now();
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where($dateColumn, $today)
            ->first();

        return response()->json([
            'data' => $attendance,
            'is_working_day' => WorkScheduleResolver::isWorkingDay($now, $user->id),
            'is_holiday' => WorkScheduleResolver::isHoliday($now),
            'holiday_label' => WorkScheduleResolver::holidayLabel($now),
            'off_reason' => WorkScheduleResolver::reasonNotWorking($now, $user->id),
            'work_hours' => WorkScheduleResolver::hoursFor($now, $user->id),
        ]);
    }

    // POST /attendances/check-in
    // JANGAN DIUBAH STRUKTURNYA — endpoint ini dipakai project mobile,
    // terima foto sebagai file upload biasa (bukan base64).
    public function checkIn(Request $request)
    {
        $user = $request->user();

        if ($blocked = $this->blockIfNotWorkingDay($user->id)) {
            return $blocked;
        }

        if ($this->hasApprovedLeaveToday($user->id)) {
            return response()->json([
                'message' => 'Kamu memiliki izin yang sudah disetujui untuk hari ini, sehingga tidak dapat absen masuk.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:1024',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = $this->now()->toDateString();
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();
        $checkInPhotoColumn = Attendance::checkInPhotoColumn();
        $locationColumn = Attendance::locationColumn();

        $existing = Attendance::where('user_id', $user->id)->where($dateColumn, $today)->first();
        if ($existing && $existing->{$checkInTimeColumn}) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini'], 422);
        }

        $photoPath = $request->file('photo')->store('attendances', 'public');

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, $dateColumn => $today],
            [
                $checkInTimeColumn => now()->toTimeString(),
                $checkInPhotoColumn => $photoPath,
                $locationColumn => $request->location,
                'status' => 'hadir',
            ]
        );

        return response()->json(['data' => $attendance], 201);
    }

    // POST /attendances/check-in-web
    // Endpoint BARU, terpisah dari checkIn() di atas — khusus dipakai halaman
    // web "Absen Masuk" yang deteksi wajah via kamera & kirim foto base64
    // (sama polanya kayak checkOut() di bawah). Nggak menyentuh/mengganti
    // endpoint checkIn() yang dipakai mobile.
    public function checkInWeb(Request $request)
    {
        $user = $request->user();

        if ($blocked = $this->blockIfNotWorkingDay($user->id)) {
            return $blocked;
        }

        if ($this->hasApprovedLeaveToday($user->id)) {
            return response()->json([
                'message' => 'Kamu memiliki izin yang sudah disetujui untuk hari ini, sehingga tidak dapat absen masuk.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Sebelumnya di sini pakai now()->toDateString() tanpa timezone,
        // beda sendiri dibanding method lain. Disamakan supaya baris
        // absensi hari ini nggak kepecah jadi 2 tanggal berbeda.
        $today = $this->now()->toDateString();
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();
        $checkInPhotoColumn = Attendance::checkInPhotoColumn();

        $existing = Attendance::where('user_id', $user->id)->where($dateColumn, $today)->first();
        if ($existing && $existing->{$checkInTimeColumn}) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini'], 422);
        }

        $photoPath = $this->saveBase64Photo($request->photo, $user->id);

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, $dateColumn => $today],
            [
                $checkInTimeColumn => $this->now()->toTimeString(),
                $checkInPhotoColumn => $photoPath,
                'location' => $request->location,
                'status' => 'hadir',
            ]
        );

        return response()->json(['data' => $attendance, 'message' => 'Absen masuk berhasil'], 201);
    }

    // POST /attendances/check-out
    // Menerima foto sebagai base64 dari kamera (dipakai halaman "Absen Pulang"
    // yang cuma deteksi wajah biasa, tanpa liveness challenge).
    public function checkOut(Request $request)
    {
        $user = $request->user();

        if ($blocked = $this->blockIfNotWorkingDay($user->id, false)) {
            return $blocked;
        }

        if ($this->hasApprovedLeaveToday($user->id)) {
            return response()->json([
                'message' => 'Kamu memiliki izin yang sudah disetujui untuk hari ini, sehingga sudah dianggap pulang.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|string',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = $this->now()->toDateString();
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();
        $checkOutTimeColumn = Attendance::checkOutTimeColumn();
        $checkOutPhotoColumn = Attendance::checkOutPhotoColumn();

        $attendance = Attendance::where('user_id', $user->id)->where($dateColumn, $today)->first();

        if (!$attendance || !$attendance->{$checkInTimeColumn}) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini'], 422);
        }
        if ($attendance->{$checkOutTimeColumn}) {
            return response()->json(['message' => 'Kamu sudah absen keluar hari ini'], 422);
        }

        $photoPath = $this->saveBase64Photo($request->photo, $user->id);

        $attendance->update([
            $checkOutTimeColumn => $this->now()->toTimeString(),
            $checkOutPhotoColumn => $photoPath,
            'location' => $request->location ?? $attendance->location,
        ]);

        return response()->json(['data' => $attendance, 'message' => 'Absen pulang berhasil']);
    }

    // POST /attendances/check-out-web
    // Endpoint khusus halaman web "Absen Pulang" yang menerima base64 dari kamera.
    public function checkOutWeb(Request $request)
    {
        $user = $request->user();

        if ($blocked = $this->blockIfNotWorkingDay($user->id, false)) {
            return $blocked;
        }

        if ($this->hasApprovedLeaveToday($user->id)) {
            return response()->json([
                'message' => 'Kamu memiliki izin yang sudah disetujui untuk hari ini, sehingga sudah dianggap pulang.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|string',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = $this->now()->toDateString();
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();
        $checkOutTimeColumn = Attendance::checkOutTimeColumn();
        $checkOutPhotoColumn = Attendance::checkOutPhotoColumn();

        $attendance = Attendance::where('user_id', $user->id)->where($dateColumn, $today)->first();

        if (!$attendance || !$attendance->{$checkInTimeColumn}) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini'], 422);
        }
        if ($attendance->{$checkOutTimeColumn}) {
            return response()->json(['message' => 'Kamu sudah absen keluar hari ini'], 422);
        }

        $photoPath = $this->saveBase64Photo($request->photo, $user->id);

        $attendance->update([
            $checkOutTimeColumn => $this->now()->toTimeString(),
            $checkOutPhotoColumn => $photoPath,
            'location' => $request->location ?? $attendance->location,
        ]);

        return response()->json(['data' => $attendance, 'message' => 'Absen pulang berhasil'], 200);
    }

    /**
     * Waktu sekarang dalam timezone aplikasi (default Asia/Jakarta).
     */
    private function now(): Carbon
    {
        return Carbon::now(config('app.timezone', 'Asia/Jakarta'));
    }

    /**
     * Blokir aksi absensi kalau hari ini bukan hari kerja (tanggal merah
     * atau hari libur sesuai jadwal mingguan peserta). Ini dipasang di
     * SEMUA endpoint check-in/check-out supaya nggak bisa dibypass lewat
     * pemanggilan API langsung — frontend cuma bertugas menampilkan pesan.
     *
     * @param bool $strictForCheckOut  Untuk check-out dipakai false: kalau
     *        user terlanjur punya absen masuk di tanggal itu (misalnya admin
     *        baru menandai tanggal merah setelah dia check-in), dia tetap
     *        boleh menutup absensinya. Selebihnya tetap diblokir.
     */
    private function blockIfNotWorkingDay(int $userId, bool $strictForCheckOut = true): ?JsonResponse
    {
        $now = $this->now();

        $reason = WorkScheduleResolver::reasonNotWorking($now, $userId);

        if (!$reason) {
            return null;
        }

        if (!$strictForCheckOut && $this->hasCheckedInToday($userId)) {
            return null;
        }

        return response()->json(['message' => $reason], 403);
    }

    private function hasCheckedInToday(int $userId): bool
    {
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();

        return Attendance::where('user_id', $userId)
            ->where($dateColumn, $this->now()->toDateString())
            ->whereNotNull($checkInTimeColumn)
            ->exists();
    }

    private function hasApprovedLeaveToday(int $userId): bool
    {
        $today = $this->now()->toDateString();

        return LeaveRequest::where('user_id', $userId)
            ->whereDate('date', $today)
            ->where('status', 'approved')
            ->exists();
    }

    private function saveBase64Photo(string $base64, int $userId): ?string
    {
        try {
            $data = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $decoded = base64_decode($data);
            if (!$decoded) return null;

            $path = "attendances/{$userId}_" . time() . '.jpg';
            Storage::disk('public')->put($path, $decoded);
            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}