<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Models\Intern;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminFaceController extends Controller
{
    // ── GET /admin/face/interns ──────────────────────────────────────────────
    // Daftar semua intern beserta status face profile-nya
    public function internList()
    {
        $interns = Intern::with(['user', 'faceProfile'])
            ->get()
            ->map(fn($intern) => [
                'intern_id'        => $intern->id,
                'user_id'          => $intern->user_id,
                'name'             => $intern->user?->name,
                'email'            => $intern->user?->email,
                'photo'            => $intern->user?->photo,
                'institusi_asal'   => $intern->institusi_asal,
                'start_date'       => $intern->start_date?->format('Y-m-d'),
                'end_date'         => $intern->end_date?->format('Y-m-d'),
                'has_face_profile' => $intern->faceProfile !== null,
                'face_photo'       => $intern->faceProfile?->photo
                    ? asset('storage/' . $intern->faceProfile->photo)
                    : null,
            ]);

        return response()->json(['data' => $interns]);
    }

    // ── POST /admin/face/register/{internId} ─────────────────────────────────
    // Admin mendaftarkan / update wajah untuk intern tertentu
    // Body: { descriptor: number[] (128 nilai), photo?: base64 string }
    public function registerForIntern(Request $request, int $internId)
    {
        $intern = Intern::with('user')->find($internId);
        if (!$intern) {
            return response()->json(['message' => 'Intern tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'descriptor' => 'required|array|size:128',
            'photo'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // Simpan foto
        $photoPath = null;
        if ($request->filled('photo')) {
            $photoPath = $this->saveBase64Photo($request->photo, $internId);
        }

        // Jika ada foto lama, hapus
        $existing = FaceProfile::where('intern_id', $internId)->first();
        if ($existing && $existing->photo && $photoPath) {
            Storage::disk('public')->delete($existing->photo);
        }

        $faceProfile = FaceProfile::updateOrCreate(
            ['intern_id' => $internId],
            [
                'descriptor' => $request->descriptor,
                'photo'      => $photoPath ?? $existing?->photo,
            ]
        );

        return response()->json([
            'message' => "Face profile untuk {$intern->user?->name} berhasil didaftarkan",
            'data'    => [
                'intern_id'  => $internId,
                'name'       => $intern->user?->name,
                'face_photo' => $faceProfile->photo
                    ? asset('storage/' . $faceProfile->photo)
                    : null,
            ],
        ]);
    }

    // ── DELETE /admin/face/{internId} ────────────────────────────────────────
    // Admin hapus face profile intern
    public function deleteProfile(int $internId)
    {
        $profile = FaceProfile::where('intern_id', $internId)->first();
        if (!$profile) {
            return response()->json(['message' => 'Face profile tidak ditemukan'], 404);
        }

        if ($profile->photo) {
            Storage::disk('public')->delete($profile->photo);
        }

        $profile->delete();

        return response()->json(['message' => 'Face profile berhasil dihapus']);
    }

    // ── POST /admin/face/impersonate/{internId} ──────────────────────────────
    // Admin login sebagai intern — generate JWT token atas nama intern
    // Berguna untuk testing / troubleshooting
    public function impersonate(int $internId)
    {
        $intern = Intern::with('user')->find($internId);
        if (!$intern || !$intern->user) {
            return response()->json(['message' => 'Intern tidak ditemukan'], 404);
        }

        $token = auth('api')->login($intern->user);
        if (!$token) {
            return response()->json(['message' => 'Gagal generate token'], 500);
        }

        return response()->json([
            'message' => "Berhasil login sebagai {$intern->user->name}",
            'user'    => $intern->user,
            'token'   => $token,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────────────────
    private function saveBase64Photo(string $base64, int $internId): ?string
    {
        try {
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
