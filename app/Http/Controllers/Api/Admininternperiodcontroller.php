<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Kelola periode magang (tanggal_mulai & tanggal_selesai) tiap peserta magang.
 * Dipakai admin buat ngatur countdown & progress yang muncul di Dashboard
 * peserta magang (InternController::estimation()).
 */
class AdminInternPeriodController extends Controller
{
    // GET /admin/intern-periods
    public function index()
    {
        $interns = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'photo', 'tanggal_mulai', 'tanggal_selesai']);

        return response()->json([
            'success' => true,
            'data' => $interns,
        ]);
    }

    // PUT /admin/intern-periods/{user}
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Periode magang berhasil diperbarui',
            'data' => $user->only(['id', 'name', 'tanggal_mulai', 'tanggal_selesai']),
        ]);
    }
}