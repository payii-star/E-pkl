<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Models\Intern;
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
        $profiles = FaceProfile::with('intern.user')
            ->get()
            ->map(function ($fp) {
                return [
                    'intern_id'  => $fp->intern_id,
                    'user_id'    => $fp->intern?->user_id,
                    'name'       => $fp->intern?->user?->name,
                    'photo'      => $fp->intern?->user?->photo,
                    'descriptor' => $fp->descriptor,  // array 128 nilai
                ];
            });

        return response()->json(['data' => $profiles]);
    }

    // ── POST /face/register ──────────────────────────────────────────────────
    // Daftar / update face profile intern yang sedang login
    // Body: { descriptor: number[] (128 nilai), photo?: base64 string }
    public function register(Request $request)
    {
        $intern = $request->user()->intern;
        if (!$intern) {
            return response()->json(['message' => 'Data peserta magang tidak ditemukan'], 404);
        }

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
            $photoPath = $this->saveBase64Photo($request->photo, $intern->id);
        }

        $faceProfile = FaceProfile::updateOrCreate(
            ['intern_id' => $intern->id],
            [
                'descriptor' => $request->descriptor,
                'photo'      => $photoPath ?? FaceProfile::where('intern_id', $intern->id)->value('photo'),
            ]
        );

        return response()->json([
            'message' => 'Face profile berhasil didaftarkan',
            'data'    => $faceProfile,
        ]);
    }

    // ── POST /face/login ─────────────────────────────────────────────────────
    // Dipanggil Vue setelah wajah cocok (pencocokan dilakukan di client/Vue)
    // Body: { intern_id: number }
    // Return: JWT token
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intern_id' => 'required|integer|exists:interns,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $intern = Intern::with('user')->find($request->intern_id);
        if (!$intern || !$intern->user) {
            return response()->json(['message' => 'Intern tidak ditemukan'], 404);
        }

        // Login sebagai user intern — generate JWT token
        $token = auth('api')->login($intern->user);
        if (!$token) {
            return response()->json(['message' => 'Gagal generate token'], 500);
        }

        return response()->json([
            'user'  => $intern->user,
            'token' => $token,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────────────────
    private function saveBase64Photo(string $base64, int $internId): ?string
    {
        try {
            // Hapus prefix data URL jika ada (data:image/jpeg;base64,...)
            $data    = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $decoded = base64_decode($data);
            if (!$decoded) return null;

            $path = "face-profiles/{$internId}_" . time() . '.jpg';
            Storage::disk('public')->put($path, $decoded);
            return $path;
        } catch (\Throwable) {
            return null;
        }
    }
}