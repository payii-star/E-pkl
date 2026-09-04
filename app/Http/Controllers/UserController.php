<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule; 
use Illuminate\Validation\Rules\Password; 

class UserController extends Controller
{
    /**
     * Display a paginated list of the resource.
     * PENTING: Kita akan gunakan method ini untuk mengambil data user.
     */
    public function index(Request $request)
    {
        $per = $request->per ?? 10;
        
        // Penambahan: Dapatkan ID dan nama role dari user yang sedang login
        $authUserId = auth()->id();
        $authUserRoleName = auth()->user()->roles->first()->name;

        $data = User::with('roles')
            // Filter 1: Jangan tampilkan akun user itu sendiri
            ->where('id', '!=', $authUserId)
            // Filter 2: Jangan tampilkan user lain yang memiliki role yang sama (misal: admin lain)
            ->whereHas('roles', function ($query) use ($authUserRoleName) {
                $query->where('name', '!=', $authUserRoleName);
            })
            ->when($request->search, function (Builder $query, string $search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            })->latest()->paginate($per);

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) // Kita gunakan Request biasa untuk sementara
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name', // Validasi berdasarkan nama peran
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
        ]);
        
        // Tetapkan peran berdasarkan NAMA
        $user->assignRole($validatedData['role']);

        return response()->json($user, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user) // Kita gunakan Request biasa
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email,' . $user->id,
            'role' => 'required|string|exists:roles,name',
            'password' => 'nullable|string|min:8',
        ]);
        
        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        
        if (!empty($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }

        $user->save();

        // Update peran berdasarkan NAMA
        $user->syncRoles($validatedData['role']);

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->deleteUserAssets($user);
        $user->delete();

        return response()->json(['success' => true]);
    }

    public function deleteProfile(Request $request)
    {
           /** @var \App\Models\User $user */
        $user = auth()->user();

        $validatedData = $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($validatedData['password'], $user->password)) {
            return response()->json(['message' => 'Password yang Anda masukkan salah.'], 422);
        }

        $this->deleteUserAssets($user);
        $user->delete();

        auth()->logout();

        return response()->json(['success' => true]);
    }

    // Method 'get' dan 'show' tidak lagi terlalu diperlukan jika 'index' sudah menyertakan role.
    // Tapi kita bisa membiarkannya untuk saat ini.

    public function updateProfile(Request $request)
    {
        // 1. Dapatkan user yang sedang login
           /** @var \App\Models\User $user */
        $user = auth()->user();

        // 2. Validasi data yang masuk
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'nim_nis' => ['required', 'string', 'max:100'],
            'asal_instansi' => ['required', 'string', 'max:255'],
            'asal_instansi_address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'asal_instansi_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'asal_instansi_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'asal_instansi_place_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Foto tidak wajib
            'remove_photo' => 'nullable|boolean',
        ]);

        // 3. Handle foto: bisa upload baru, atau hapus (tidak bisa dua-duanya sekaligus)
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada untuk menghemat penyimpanan
            if ($user->photo) {
                // Hapus path relatif dari nama file, jika disimpan dengan path
                $oldPhotoPath = str_replace('/storage/', '', $user->photo);
                Storage::disk('public')->delete($oldPhotoPath);
            }

            // Simpan foto baru dan dapatkan path-nya
            $path = $request->file('photo')->store('photos', 'public');
            // Simpan path lengkap untuk kemudahan di frontend
            $user->photo = '/storage/' . $path;
        } elseif ($request->boolean('remove_photo')) {
            // User memilih "Hapus Foto" dan tidak mengunggah foto baru
            if ($user->photo) {
                $oldPhotoPath = str_replace('/storage/', '', $user->photo);
                Storage::disk('public')->delete($oldPhotoPath);
            }
            $user->photo = null;
        }

        // 4. Update data user
        $user->name = $validatedData['name'];
        $user->phone = $validatedData['phone'];
        $user->nim_nis = $validatedData['nim_nis'];
        $user->asal_instansi = $validatedData['asal_instansi'];
        foreach ([
            'asal_instansi_address',
            'asal_instansi_latitude',
            'asal_instansi_longitude',
            'asal_instansi_place_id',
        ] as $field) {
            if (array_key_exists($field, $validatedData)) {
                $user->{$field} = $validatedData[$field];
            }
        }

        // 5. Simpan perubahan
        $user->save();

        // 6. Kembalikan data user yang sudah diperbarui
        return response()->json($user);
    }

    public function changeEmail(Request $request)
    {
           /** @var \App\Models\User $user */
        $user = auth()->user();

        // 1. Validasi input
        $validatedData = $request->validate([
            'emailaddress' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'confirmemailpassword' => 'required|string',
        ]);

        // 2. Verifikasi password saat ini
        if (!Hash::check($validatedData['confirmemailpassword'], $user->password)) {
            return response()->json(['message' => 'Password yang Anda masukkan salah.'], 422);
        }

        // 3. Update email
        $user->email = $validatedData['emailaddress'];
        // Opsional: Jika sistem Anda menggunakan verifikasi email, reset statusnya
        // $user->email_verified_at = null; 
        $user->save();

        return response()->json([
            'message' => 'Email berhasil diperbarui.',
            'user' => $user
        ]);
    }

    /**
     * Method baru untuk mengubah password user yang sedang login.
     */
    public function changePassword(Request $request)
    {
           /** @var \App\Models\User $user */
        $user = auth()->user();

        // 1. Validasi input
        $validatedData = $request->validate([
            'currentpassword' => 'required|string',
            'newpassword' => ['required', 'string', Password::min(8)],
            'confirmpassword' => 'required|string|same:newpassword',
        ]);

        // 2. Verifikasi password saat ini
        if (!Hash::check($validatedData['currentpassword'], $user->password)) {
            return response()->json(['message' => 'Password saat ini salah.'], 422);
        }

        // 3. Update password
        $user->password = Hash::make($validatedData['newpassword']);
        $user->save();

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }

    private function deleteUserAssets(User $user): void
    {
        if ($user->photo) {
            $photoPath = str_replace('/storage/', '', $user->photo);
            Storage::disk('public')->delete($photoPath);
        }

        if ($user->relationLoaded('faceProfile') ? $user->faceProfile : $user->faceProfile()->exists()) {
            $faceProfile = $user->relationLoaded('faceProfile') ? $user->faceProfile : $user->faceProfile()->first();

            if ($faceProfile && $faceProfile->photo) {
                Storage::disk('public')->delete($faceProfile->photo);
            }

            if ($faceProfile) {
                $faceProfile->delete();
            }
        }
    }
}