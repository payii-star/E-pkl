<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    // ── GET /attendances ─────────────────────────────────────────────────────
    // Riwayat absensi intern yang sedang login
    public function index(Request $request)
    {
        $intern = $request->user()->intern;
        if (!$intern) {
            return response()->json(['data' => []]);
        }

        $attendances = Attendance::where('intern_id', $intern->id)
            ->latest('date')
            ->get();

        return response()->json(['data' => $attendances]);
    }

    // ── GET /attendances/today ───────────────────────────────────────────────
    // Absensi hari ini — dipakai Vue untuk cek sudah check-in/out atau belum
    public function today(Request $request)
    {
        $intern = $request->user()->intern;
        if (!$intern) {
            return response()->json(['data' => null]);
        }

        $today = now()->toDateString();
        $attendance = Attendance::where('intern_id', $intern->id)
            ->where('date', $today)
            ->first();

        return response()->json(['data' => $attendance]);
    }

    // ── POST /attendances/check-in ───────────────────────────────────────────
    // Menerima foto sebagai base64 string (dikirim dari Vue face recognition)
    public function checkIn(Request $request)
    {
        $intern = $request->user()->intern;
        if (!$intern) {
            return response()->json(['message' => 'Data peserta magang tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo'    => 'nullable|string',   // base64 string dari kamera
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = now()->toDateString();

        // Cek sudah check-in hari ini
        $existing = Attendance::where('intern_id', $intern->id)
            ->where('date', $today)
            ->first();

        if ($existing && $existing->check_in_time) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini'], 422);
        }

        // Simpan foto
        $photoPath = null;
        if ($request->filled('photo')) {
            $photoPath = $this->saveBase64Photo($request->photo, $intern->id, 'checkin');
        }

        // Hitung status: on_time jika <= 07:00, late jika > 07:00
        $now        = Carbon::now();
        $shiftStart = Carbon::today()->setTime(7, 0, 0);
        $status     = $now->gt($shiftStart) ? 'terlambat' : 'hadir';

        $attendance = Attendance::updateOrCreate(
            ['intern_id' => $intern->id, 'date' => $today],
            [
                'check_in_time'  => $now->toTimeString(),
                'check_in_photo' => $photoPath,
                'location'       => $request->location,
                'status'         => $status,
            ]
        );

        $message = $status === 'terlambat'
            ? 'Check-in berhasil. Kamu terlambat ' . $now->diffInMinutes($shiftStart) . ' menit.'
            : 'Check-in berhasil. Tepat waktu!';

        return response()->json([
            'message' => $message,
            'data'    => $attendance,
        ], 201);
    }

    // ── POST /attendances/check-out ──────────────────────────────────────────
    public function checkOut(Request $request)
    {
        $intern = $request->user()->intern;
        if (!$intern) {
            return response()->json(['message' => 'Data peserta magang tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'nullable|string',   // base64 string
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = now()->toDateString();
        $attendance = Attendance::where('intern_id', $intern->id)
            ->where('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini'], 422);
        }

        if ($attendance->check_out_time) {
            return response()->json(['message' => 'Kamu sudah absen keluar hari ini'], 422);
        }

        // Simpan foto
        $photoPath = null;
        if ($request->filled('photo')) {
            $photoPath = $this->saveBase64Photo($request->photo, $intern->id, 'checkout');
        }

        $attendance->update([
            'check_out_time'  => now()->toTimeString(),
            'check_out_photo' => $photoPath,
        ]);

        return response()->json([
            'message' => 'Check-out berhasil. Sampai jumpa besok!',
            'data'    => $attendance,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────────────────
    private function saveBase64Photo(string $base64, int $internId, string $type): ?string
    {
        try {
            $data    = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $decoded = base64_decode($data);
            if (!$decoded) return null;

            $path = "attendances/{$type}_{$internId}_" . time() . '.jpg';
            Storage::disk('public')->put($path, $decoded);
            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}