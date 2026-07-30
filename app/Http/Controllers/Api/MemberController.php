<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // Import Str untuk random string
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    /**
     * Menampilkan daftar semua member.
     */
    public function index()
    {
        return Member::latest()->orderBy('name', 'asc')->paginate(15);
    }

    /**
     * MENYIMPAN MEMBER BARU (INI YANG HILANG SEBELUMNYA)
     */
    public function store(Request $request)
    {
        // 1. Validasi Input dari Admin (Hanya Nama, HP, Email, Alamat)
        // Admin TIDAK PERLU input password
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:members,phone_number',
            'email' => 'nullable|email|unique:members,email',
            'address' => 'nullable|string',
        ]);

        // 2. Generate Data Otomatis oleh Sistem
        $validated['member_id'] = 'MBR-' . time() . rand(100, 999); // ID Unik
        $validated['points'] = 0; // Poin awal 0
        
        // --- SOLUSI PASSWORD ---
        // Kita set default '123456'. 
        // Admin/Kasir nanti infokan ke member: "Password sementaranya 123456 ya kak"
        $validated['password'] = Hash::make('123456'); 

        $member = Member::create($validated);

        return response()->json($member, 201);
    }

    /**
     * Menampilkan satu data member.
     */
    public function show(Member $member)
    {
        return $member;
    }

    /**
     * Memperbarui data member yang ada.
     */
    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:members,phone_number,' . $member->id,
            'email' => 'nullable|email|unique:members,email,' . $member->id,
            'address' => 'nullable|string',
        ]);

        $member->update($validated);

        return response()->json($member);
    }

    /**
     * Menghapus data member.
     */
    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(null, 204);
    }

    /**
     * Mencari member.
     */
    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string']);
        $searchTerm = $request->input('query');
    
        $member = Member::where('member_id', $searchTerm)
                            ->orWhere('phone_number', $searchTerm)
                            ->orWhere('id', $searchTerm)
                            ->first();
    
        if (!$member) {
            return response()->json(['message' => 'Member not found'], 404);
        }
    
        return response()->json($member);
    }
}