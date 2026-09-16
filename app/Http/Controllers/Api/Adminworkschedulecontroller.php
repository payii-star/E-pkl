<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicHoliday;
use App\Models\WorkSchedule;
use App\Support\WorkScheduleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminWorkScheduleController extends Controller
{
    private const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    // GET /admin/work-schedule -> jadwal mingguan (urut Senin-Minggu) + daftar tanggal merah
    public function index()
    {
        $schedules = WorkSchedule::all()->keyBy('day');

        $ordered = collect(self::DAY_ORDER)
            ->map(fn ($day) => $schedules->get($day))
            ->filter()
            ->values();

        $holidays = PublicHoliday::orderBy('date')->get();

        return response()->json([
            'data' => $ordered,
            'holidays' => $holidays,
        ]);
    }

    // PUT /admin/work-schedule/{day} -> update jadwal 1 hari
    public function update(Request $request, string $day)
    {
        if (!in_array($day, self::DAY_ORDER, true)) {
            return response()->json(['message' => 'Hari tidak valid'], 422);
        }

        $validator = Validator::make($request->all(), [
            'is_working_day' => 'required|boolean',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'min_check_in_time' => 'required|date_format:H:i',
            'max_check_out_time' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $schedule = WorkSchedule::firstOrNew(['day' => $day]);
        $schedule->fill($validator->validated());
        $schedule->save();

        WorkScheduleResolver::clearCache();

        return response()->json([
            'data' => $schedule,
            'message' => 'Jadwal berhasil disimpan',
        ]);
    }

    // POST /admin/work-schedule/holidays -> tambah tanggal merah
    public function storeHoliday(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|unique:public_holidays,date',
            'label' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $holiday = PublicHoliday::create([
            'date' => $request->date,
            'label' => $request->label,
            'created_by' => $request->user()->id,
        ]);

        WorkScheduleResolver::clearCache();

        return response()->json([
            'data' => $holiday,
            'message' => 'Tanggal merah berhasil ditambahkan',
        ], 201);
    }

    // DELETE /admin/work-schedule/holidays/{holiday}
    public function destroyHoliday(PublicHoliday $holiday)
    {
        $holiday->delete();

        WorkScheduleResolver::clearCache();

        return response()->json(['message' => 'Tanggal merah berhasil dihapus']);
    }
}