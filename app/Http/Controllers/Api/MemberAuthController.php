<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password; // Import class Password

class MemberAuthController extends Controller
{
    /**
     * Handle a registration request for a member.
     */
    public function register(Request $request)
    {
        // 1. Validasi data yang masuk
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:members',
            'phone_number' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // 2. Buat member baru
        $member = Member::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        // 3. Buat token agar member bisa langsung login
        $token = $member->createToken('member-registration-token')->plainTextToken;

        // 4. Kembalikan respons sukses
        return response()->json([
            'message' => 'Registrasi berhasil!',
            'member' => $member,
            'token' => $token,
        ], 201); // 201 Created
    }

    /**
     * Handle a login request for a member.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $member = Member::where('email', $request->email)->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $member->createToken('member-login-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil!',
            'token' => $token,
        ]);
    }
}