<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * GET /api/attendances
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $attendances = Attendance::where('user_id', $user->id)
            ->latest('date')
            ->get();

        return response()->json([
            'data' => $attendances,
        ]);
    }

    /**
     * GET /api/attendances/today
     */
    public function today(Request $request)
    {
        $user = $request->user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        return response()->json([
            'data' => $attendance,
        ]);
    }

    /**
     * POST /api/attendances/check-in
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'nullable|string',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $today = now()->toDateString();

        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing && $existing->check_in_time) {
            return response()->json([
                'message' => 'Kamu sudah absen masuk hari ini.',
            ], 422);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ],
            [
                'check_in_time' => now()->toTimeString(),
                'location' => $request->input('location'),
                'status' => 'hadir',
            ]
        );

        return response()->json([
            'message' => 'Check-in berhasil.',
            'data' => $attendance,
        ], 201);
    }

    /**
     * POST /api/attendances/check-out
     */
    public function checkOut(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json([
                'message' => 'Kamu belum absen masuk hari ini.',
            ], 422);
        }

        if ($attendance->check_out_time) {
            return response()->json([
                'message' => 'Kamu sudah absen keluar hari ini.',
            ], 422);
        }

        $attendance->update([
            'check_out_time' => now()->toTimeString(),
        ]);

        return response()->json([
            'message' => 'Check-out berhasil.',
            'data' => $attendance->fresh(),
        ]);
    }
}