<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FaceController extends Controller
{
    // ── GET /face/profiles ───────────────────────────────────────────────────
    // Mengembalikan semua face profile yang tersimpan
    // Dipakai Vue untuk mencocokkan wajah saat login / absensi
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
                    'descriptor' => $fp->descriptor,  // array 128 nilai
                ];
            });

        return response()->json(['data' => $profiles]);
    }

    // ── POST /face/register ──────────────────────────────────────────────────
    // Daftar / update face profile user yang sedang login
    // Body: { descriptor: number[] (128 nilai), photo?: base64 string }
    public function register(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'descriptor' => 'required|array|size:128',
            'photo'      => 'nullable|string', // base64
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Simpan foto jika ada
        $photoPath = null;
        if ($request->filled('photo')) {
            $photoPath = $this->saveBase64Photo($request->photo, $user->id);
        }

        $faceProfile = FaceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'descriptor' => $request->descriptor,
                'photo'      => $photoPath ?? FaceProfile::where('user_id', $user->id)->value('photo'),
            ]
        );

        return response()->json([
            'message' => 'Face profile berhasil didaftarkan',
            'data'    => $faceProfile,
        ]);
    }

    // ── POST /face/login ─────────────────────────────────────────────────────
    // Dipanggil Vue setelah wajah cocok (pencocokan dilakukan di client/Vue)
    // Body: { user_id: number }
    // Return: JWT token
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

        // Login sebagai user — generate JWT token
        $token = auth('api')->login($user);
        if (!$token) {
            return response()->json(['message' => 'Gagal generate token'], 500);
        }

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────────────────
    private function saveBase64Photo(string $base64, int $userId): ?string
    {
        try {
            // Hapus prefix data URL jika ada (data:image/jpeg;base64,...)
            $data    = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
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