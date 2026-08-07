<?php

namespace App\Http\Controllers;

use App\Exceptions\DuplicateFaceException;
use App\Models\FaceProfile;
use App\Models\User;
use App\Traits\DetectsDuplicateFace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use DetectsDuplicateFace;

    private const MIN_FACE_SAMPLES = 3;
    private const MAX_FACE_SAMPLES = 10;

    public function me()
    {
        return response()->json([
            'user' => auth()->user()
        ]);
    }

    /**
     * @deprecated Sign-up publik sekarang wajib lewat registerWithFace() supaya
     * akun tidak pernah tercipta tanpa wajah terdaftar. Method ini dibiarkan
     * untuk kompatibilitas internal (mis. dipanggil dari seeder/tinker/testing),
     * TAPI jangan diarahkan ke route publik sign-up lagi.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => 'aktif',
        ]);

        $user->assignRole('karyawan');

        $token = auth('api')->login($user);
        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Akun dibuat, tapi gagal login otomatis. Silakan login manual.'], 500);
        }

        return response()->json([
            'status' => true,
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Sign-up publik (satu-satunya jalur yang boleh dipakai frontend).
     * Membuat akun + face profile dalam satu transaksi atomik:
     * - Kalau descriptor wajah duplikat/tidak valid → SELURUH proses rollback,
     *   user TIDAK PERNAH tercipta di database.
     * - Token baru diterbitkan setelah semua langkah sukses, jadi tidak ada
     *   state "akun ada tapi wajah belum terdaftar" yang bisa diakses via refresh.
     *
     * Body: { name, email, phone?, password, password_confirmation,
     *         descriptors: number[][], photo?: base64 }
     */
    public function registerWithFace(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'descriptors' => 'required|array|min:' . self::MIN_FACE_SAMPLES . '|max:' . self::MAX_FACE_SAMPLES,
            'descriptors.*' => 'array|size:128',
            'descriptors.*.*' => 'numeric',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $user = DB::transaction(function () use ($request) {
                // 1. Hitung descriptor rata-rata & pastikan belum dipakai akun lain.
                //    Dicek SEBELUM user dibuat supaya tidak ada jejak user "gagal" di DB.
                $avgDescriptor = $this->averageDescriptors($request->descriptors);
                $this->assertFaceNotDuplicate($avgDescriptor);

                // 2. Baru buat user setelah wajah dipastikan aman.
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => Hash::make($request->password),
                    'status' => 'aktif',
                ]);

                $user->assignRole('karyawan');

                // 3. Simpan foto (kalau ada) & face profile.
                $photoPath = $request->filled('photo')
                    ? $this->saveBase64Photo($request->photo, $user->id)
                    : null;

                FaceProfile::create([
                    'user_id' => $user->id,
                    'descriptor' => $avgDescriptor,
                    'photo' => $photoPath,
                ]);

                return $user;
            });
        } catch (DuplicateFaceException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $token = auth('api')->login($user);
        if (!$token) {
            // Kondisi langka: user & face profile sudah tersimpan tapi login gagal.
            // Tetap balikan sukses supaya user tidak bingung, arahkan untuk login manual.
            return response()->json([
                'status' => true,
                'user' => $user,
                'token' => null,
                'message' => 'Akun & wajah berhasil didaftarkan, tapi gagal login otomatis. Silakan login manual.',
            ], 201);
        }

        return response()->json([
            'status' => true,
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->post(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json([
                'status' => false,
                'message' => 'Email / Password salah!'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'user' => auth()->user(),
            'token' => $token
        ]);
    }

    public function logout()
    {
        auth()->logout();
        return response()->json(['success' => true]);
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