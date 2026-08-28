<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\DuplicateFaceException;
use App\Models\Attendance;
use App\Models\FaceProfile;
use App\Models\User;
use App\Traits\DetectsDuplicateFace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FaceController extends Controller
{
    use DetectsDuplicateFace;

    private const MIN_SAMPLES = 3;
    private const MAX_SAMPLES = 10;

    // ── GET /face/profiles ───────────────────────────────────────────────────
    public function profiles()
    {
        $profiles = FaceProfile::with('user')
            ->get()
            ->map(function ($fp) {
                return [
                    'intern_id'  => $fp->user_id,
                    'user_id'    => $fp->user_id,
                    'name'       => $fp->user?->name,
                    'photo'      => $fp->user?->photo,
                    'descriptor' => $fp->descriptor,
                ];
            });

        return response()->json(['data' => $profiles]);
    }

    // ── POST /face/register ──────────────────────────────────────────────────
    // Khusus untuk RE-REGISTER wajah pada akun yang SUDAH ADA (mis. dari halaman
    // profil, karena kamera lama rusak / wajah tidak terbaca lagi). Sign-up baru
    // TIDAK memakai endpoint ini — pakai AuthController::registerWithFace().
    public function register(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'descriptors' => 'required|array|min:' . self::MIN_SAMPLES . '|max:' . self::MAX_SAMPLES,
            'descriptors.*' => 'array|size:128',
            'descriptors.*.*' => 'numeric',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $avgDescriptor = $this->averageDescriptors($request->descriptors);

        try {
            // Exclude diri sendiri: user boleh update descriptor miliknya sendiri.
            $this->assertFaceNotDuplicate($avgDescriptor, $user->id);
        } catch (DuplicateFaceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $photoPath = null;
        if ($request->filled('photo')) {
            $photoPath = $this->saveBase64Photo($request->photo, $user->id);
        }

        $faceProfile = FaceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'descriptor' => $avgDescriptor,
                'photo' => $photoPath ?? FaceProfile::where('user_id', $user->id)->value('photo'),
            ]
        );

        return response()->json([
            'message' => 'Face profile berhasil didaftarkan',
            'data' => $faceProfile,
        ]);
    }

    // ── POST /face/login ─────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $userId = $request->input('user_id', $request->input('intern_id'));
        $payload = ['user_id' => $userId];

        $validator = Validator::make($payload, [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $token = auth('api')->login($user);
        if (!$token) {
            return response()->json(['message' => 'Gagal generate token'], 500);
        }

        $attendance = $this->recordFaceLoginAttendance($user->id, $request->input('photo'));

        return response()->json([
            'user' => $user,
            'token' => $token,
            'attendance' => $attendance,
        ]);
    }

    /**
     * Catat absen masuk otomatis saat user berhasil login via face recognition.
     * Hanya login PERTAMA di hari itu yang dihitung sebagai check-in
     * (login berikutnya di hari yang sama tidak menimpa jam check-in yang sudah ada).
     */
    private function recordFaceLoginAttendance(int $userId, ?string $photoBase64 = null): Attendance
    {
        $today = now()->toDateString();
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();
        $checkInPhotoColumn = Attendance::checkInPhotoColumn();
        $locationColumn = Attendance::locationColumn();

        $existing = Attendance::where('user_id', $userId)->where($dateColumn, $today)->first();

        if ($existing && $existing->{$checkInTimeColumn}) {
            return $existing;
        }

        $photoPath = $photoBase64 ? $this->saveAttendancePhoto($photoBase64, $userId) : null;

        return Attendance::updateOrCreate(
            ['user_id' => $userId, $dateColumn => $today],
            [
                $checkInTimeColumn => now()->toTimeString(),
                $checkInPhotoColumn => $photoPath,
                'status' => 'hadir',
                $locationColumn => 'Face Recognition Login',
            ]
        );
    }

    private function saveBase64Photo(string $base64, int $userId): ?string
    {
        try {
            $data = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $decoded = base64_decode($data);
            if (!$decoded) return null;

            $path = "face-profiles/{$userId}_" . time() . '.jpg';
            Storage::disk('public')->put($path, $decoded);
            return $path;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Sama seperti saveBase64Photo(), tapi khusus buat foto absen (check-in
     * dari Login dengan Wajah) — folder terpisah dari face-profiles/ supaya
     * tidak ketuker sama foto enrollment wajah.
     */
    private function saveAttendancePhoto(string $base64, int $userId): ?string
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