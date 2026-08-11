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

        $attendance = $this->recordFaceLoginAttendance($user->id);

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
    private function recordFaceLoginAttendance(int $userId): Attendance
    {
        $today = now()->toDateString();

        $existing = Attendance::where('user_id', $userId)->where('date', $today)->first();

        if ($existing && $existing->check_in_time) {
            return $existing;
        }

        return Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $today],
            [
                'check_in_time' => now()->toTimeString(),
                'status' => 'hadir',
                'location' => 'Face Recognition Login',
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
}