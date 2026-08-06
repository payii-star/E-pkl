<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminFaceController extends Controller
{
    // ── GET /admin/face/interns ──────────────────────────────────────────────
    // Daftar semua user karyawan beserta status face profile-nya
    public function internList()
    {
        $users = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan']);
            })
            ->with('faceProfile')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'intern_id'        => $user->id,
                'user_id'          => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'photo'            => $user->photo,
                'institusi_asal'   => $user->asal_instansi,
                'start_date'       => $user->tanggal_mulai?->format('Y-m-d'),
                'end_date'         => $user->tanggal_selesai?->format('Y-m-d'),
                'has_face_profile' => $user->faceProfile !== null,
                'face_photo'       => $user->faceProfile?->photo
                    ? asset('storage/' . $user->faceProfile->photo)
                    : null,
            ]);

        return response()->json(['data' => $users]);
    }

    // ── POST /admin/face/register/{user} ────────────────────────────────────
    // Admin mendaftarkan / update wajah untuk user tertentu
    // Body: { descriptor: number[] (128 nilai), photo?: base64 string }
    public function registerForIntern(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'descriptor' => 'required|array|size:128',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $existing = $user->faceProfile;
        $photoPath = $existing?->photo;
        if ($request->filled('photo')) {
            if ($existing?->photo) {
                Storage::disk('public')->delete($existing->photo);
            }

            $photoPath = $this->saveBase64Photo($request->photo, $user->id);
        }

        $faceProfile = FaceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'descriptor' => $request->descriptor,
                'photo' => $photoPath,
            ]
        );

        return response()->json([
            'message' => "Face profile untuk {$user->name} berhasil didaftarkan",
            'data'    => [
                'intern_id'  => $user->id,
                'name'       => $user->name,
                'face_photo' => $faceProfile->photo
                    ? asset('storage/' . $faceProfile->photo)
                    : null,
            ],
        ]);
    }

    // ── DELETE /admin/face/{user} ────────────────────────────────────────────
    // Admin hapus face profile user
    public function deleteProfile(User $user)
    {
        if ($user->faceProfile?->photo) {
            Storage::disk('public')->delete($user->faceProfile->photo);
        }

        $user->faceProfile?->delete();

        return response()->json(['message' => 'Face profile berhasil dihapus']);
    }

    // ── POST /admin/face/impersonate/{user} ─────────────────────────────────
    // Admin login sebagai user — generate JWT token atas nama user
    // Berguna untuk testing / troubleshooting
    public function impersonate(User $user)
    {
        $token = auth('api')->login($user);
        if (!$token) {
            return response()->json(['message' => 'Gagal generate token'], 500);
        }

        return response()->json([
            'message' => "Berhasil login sebagai {$user->name}",
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    // ─── Private Helper ───────────────────────────────────────────────────────
    private function saveBase64Photo(string $base64, int $userId): ?string
    {
        try {
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
