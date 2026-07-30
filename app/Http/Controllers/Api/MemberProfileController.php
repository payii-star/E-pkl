<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PointMovement; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberProfileController extends Controller
{
    /**
     * Menampilkan data profil member yang sedang login.
     */
    public function show(Request $request)
    {
        $member = $request->user();

        // Mengembalikan data lengkap agar form edit bisa terisi otomatis
        return response()->json([
            'id'           => $member->id,
            'name'         => $member->name,
            'email'        => $member->email,
            'phone_number' => $member->phone_number,
            'address'      => $member->address, // Tambahkan address
            'member_id'    => $member->member_id,
            'points'       => $member->points,
        ]);
    }

    /**
     * Update data profil (Nama, Email, HP, Alamat)
     */
    public function update(Request $request)
    {
        $member = $request->user();

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            // Validasi unik, tapi abaikan (ignore) data milik user yang sedang login ini
            'email'        => ['nullable', 'email', Rule::unique('members')->ignore($member->id)],
            'phone_number' => ['required', 'string', Rule::unique('members')->ignore($member->id)],
            'address'      => 'nullable|string',
        ]);

        $member->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user'    => $member
        ]);
    }

    /**
     * Ganti Password Member
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed', // Harus ada field new_password_confirmation di frontend
        ]);

        $member = $request->user();

        // 1. Cek apakah password lama benar
        if (!Hash::check($request->current_password, $member->password)) {
            return response()->json([
                'message' => 'Password saat ini salah.'
            ], 422);
        }

        // 2. Update password baru
        $member->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diubah.'
        ]);
    }

    public function getPointHistory(Request $request)
    {
        // Tambahkan with('transaction')
        $history = PointMovement::where('member_id', $request->user()->id)
                                ->with('transaction') // <--- PENTING: Load data transaksi
                                ->latest()
                                ->paginate(10);
    
        return response()->json($history);
    }
}