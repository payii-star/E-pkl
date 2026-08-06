<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    // GET /attendances
    public function index(Request $request)
    {
        $user = $request->user();

        $attendances = Attendance::where('user_id', $user->id)
            ->latest('date')
            ->get();

        return response()->json(['data' => $attendances]);
    }

    // POST /attendances/check-in
    public function checkIn(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:1024',
            'location' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = now()->toDateString();

        $existing = Attendance::where('user_id', $user->id)->where('date', $today)->first();
        if ($existing && $existing->check_in_time) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini'], 422);
        }

        $photoPath = $request->file('photo')->store('attendances', 'public');

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'check_in_time' => now()->toTimeString(),
                'check_in_photo' => $photoPath,
                'location' => $request->location,
                'status' => 'hadir',
            ]
        );

        return response()->json(['data' => $attendance], 201);
    }

    // POST /attendances/check-out
    public function checkOut(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:1024',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $today = now()->toDateString();
        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        if (!$attendance || !$attendance->check_in_time) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini'], 422);
        }
        if ($attendance->check_out_time) {
            return response()->json(['message' => 'Kamu sudah absen keluar hari ini'], 422);
        }

        $photoPath = $request->file('photo')->store('attendances', 'public');

        $attendance->update([
            'check_out_time' => now()->toTimeString(),
            'check_out_photo' => $photoPath,
        ]);

        return response()->json(['data' => $attendance]);
    }
}