<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicHoliday;
use App\Models\WorkSchedule;
use App\Models\User;
use App\Support\WorkScheduleResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminWorkScheduleController extends Controller
{
    private const DAY_ORDER = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    // GET /admin/work-schedule/{user}?month=YYYY-MM -> jadwal mingguan + override tanggal
    public function index(Request $request, User $user)
    {
        $month = $request->query('month');
        $schedules = WorkSchedule::where('user_id', $user->id)
            ->whereNull('date')
            ->get()
            ->keyBy('day');
        $defaults = WorkSchedule::whereNull('user_id')->get()->keyBy('day');

        $ordered = collect(self::DAY_ORDER)
            ->map(fn ($day) => $schedules->get($day) ?? $defaults->get($day))
            ->filter()
            ->values();

        $dated = WorkSchedule::where('user_id', $user->id)
            ->whereNotNull('date')
            ->when($month, function ($query) use ($month) {
                $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $query->whereBetween('date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
            })
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $ordered->concat($dated)->values(),
            'user' => $user->only(['id', 'name', 'email', 'photo']),
        ]);
    }

    // PUT /admin/work-schedule/{user}/{date} -> update jadwal pada tanggal tertentu
    public function update(Request $request, User $user, string $date)
    {
        try {
            $dateValue = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Throwable) {
            return response()->json(['message' => 'Tanggal tidak valid'], 422);
        }

        $validator = Validator::make($request->all(), [
            'is_working_day' => 'required|boolean',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'min_check_in_time' => 'required|date_format:H:i',
            'apply_to_same_weekday' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();
        $day = strtolower($dateValue->englishDayOfWeek);
        $values = [
            'is_working_day' => $validated['is_working_day'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'min_check_in_time' => $validated['min_check_in_time'],
        ];

        if ($validated['apply_to_same_weekday'] ?? false) {
            $schedule = WorkSchedule::firstOrNew([
                'user_id' => $user->id,
                'date' => null,
                'day' => $day,
            ]);
            $schedule->fill($values);
            $schedule->save();

            WorkSchedule::where('user_id', $user->id)
                ->where('day', $day)
                ->whereNotNull('date')
                ->update($values);
        } else {
            $schedule = WorkSchedule::firstOrNew([
                'user_id' => $user->id,
                'date' => $dateValue->toDateString(),
            ]);
            $schedule->fill([...$values, 'day' => $day]);
            $schedule->save();
        }

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