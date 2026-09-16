<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Logic perhitungan rekap kehadiran, tugas, izin, dan nilai sistem untuk
 * 1 siswa magang. Dipakai bareng oleh AdminAssessmentController (sisi
 * admin) dan AssessmentController (sisi siswa sendiri), supaya angka yang
 * ditampilkan ke admin dan ke siswa selalu konsisten / sama persis.
 *
 * Jam kerja & status hari kerja/libur SEKARANG diambil dinamis dari
 * WorkScheduleResolver (tabel work_schedules + public_holidays), bukan
 * hardcode lagi — jadi kalau admin ubah jadwal di halaman "Jam Kerja",
 * nilai sistem otomatis ikut menyesuaikan.
 */
trait ComputesInternAssessment
{
    protected function lateGraceMinutes(): int
    {
        return 5;
    }

    protected function latePointPerStep(): int
    {
        return 1;
    }

    protected function unfinishedTaskStatuses(): array
    {
        return ['belum', 'sedang', 'revisi', 'ditolak'];
    }

    /**
     * Ringkasan lengkap 1 siswa PADA 1 tanggal tertentu.
     */
    protected function buildDaySummary(User $intern, Carbon $date): array
    {
        $hasPeriod = (bool) ($intern->tanggal_mulai && $intern->tanggal_selesai);

        $periodStart = $hasPeriod ? Carbon::parse($intern->tanggal_mulai)->startOfDay() : null;
        $periodEnd = $hasPeriod ? Carbon::parse($intern->tanggal_selesai)->startOfDay() : null;

        $attendanceToday = Attendance::where('user_id', $intern->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        $attendanceInfo = $this->buildAttendanceInfo($attendanceToday, $date);

        $allTasks = Task::where('user_id', $intern->id)->get();
        $tasksAssignedToDate = $allTasks->filter(
            fn ($t) => $t->created_at && Carbon::parse($t->created_at)->lte($date->copy()->endOfDay())
        );

        $overdueIncomplete = $tasksAssignedToDate
            ->whereIn('status', $this->unfinishedTaskStatuses())
            ->filter(fn ($t) => $t->due_date && Carbon::parse($t->due_date)->endOfDay()->lte($date->copy()->endOfDay()))
            ->values();

        $taskList = $tasksAssignedToDate->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'given_at' => Carbon::parse($t->created_at)->toDateString(),
            'due_date' => $t->due_date,
            'submitted_at' => $t->submitted_at,
            'status' => $t->status,
        ])->values();

        $taskSummary = [
            'total_assigned' => $tasksAssignedToDate->count(),
            'completed' => $tasksAssignedToDate->where('status', 'selesai')->count(),
            'overdue_incomplete' => $overdueIncomplete->count(),
        ];

        $systemScore = null;
        $scoreBreakdown = null;

        if ($hasPeriod) {
            [$systemScore, $scoreBreakdown] = $this->computeSystemScore($intern, $periodStart, $periodEnd, $date, $allTasks);
        }

        $unexcusedCount = 0;
        $leaveSummary = ['sakit' => 0, 'acara_keluarga' => 0, 'tanpa_keterangan' => 0];

        if ($hasPeriod) {
            $recapEnd = $periodEnd->copy()->min($date);
            $unexcusedCount = $this->countUnexcusedDays($intern, $periodStart, $recapEnd);
            $leaveSummary = $this->computeLeaveSummary($intern, $periodStart, $recapEnd, $unexcusedCount);
        }

        return [
            'user' => $intern->only(['id', 'name', 'email', 'photo', 'nim_nis', 'asal_instansi']),
            'period' => $hasPeriod ? [
                'start' => $periodStart->toDateString(),
                'end' => $periodEnd->toDateString(),
            ] : null,
            'has_period' => $hasPeriod,
            'attendance' => $attendanceInfo,
            'task_summary' => $taskSummary,
            'task_list' => $taskList,
            'unexcused_count' => $unexcusedCount,
            'leave_summary' => $leaveSummary,
            'system_score' => $systemScore,
            'score_breakdown' => $scoreBreakdown,
        ];
    }

    /**
     * @param Carbon|null $fallbackDate dipakai kalau $att null (nggak ada absensi),
     *                                  buat tau jam kerja resmi hari itu tetap bisa dihitung
     */
    protected function buildAttendanceInfo(?Attendance $att, ?Carbon $fallbackDate = null): array
    {
        if (!$att) {
            return [
                'check_in_time' => null,
                'check_out_time' => null,
                'is_late' => false,
                'late_minutes' => 0,
                'is_early_leave' => false,
                'early_minutes' => 0,
            ];
        }

        $refDate = $fallbackDate ?? Carbon::parse($att->date);
        $hours = WorkScheduleResolver::hoursFor($refDate);

        $lateMinutes = 0;
        $isLate = false;
        if ($att->check_in_time) {
            $checkIn = Carbon::parse($att->check_in_time);
            $workStart = Carbon::parse($checkIn->toDateString() . ' ' . $hours['start']);
            if ($checkIn->gt($workStart)) {
                $lateMinutes = $workStart->diffInMinutes($checkIn);
                $isLate = true; // tetap ditandai telat walau potongannya 0 (< 5 menit)
            }
        }

        $earlyMinutes = 0;
        $isEarlyLeave = false;
        if ($att->check_out_time) {
            $checkOut = Carbon::parse($att->check_out_time);
            $workEnd = Carbon::parse($checkOut->toDateString() . ' ' . $hours['end']);
            if ($checkOut->lt($workEnd)) {
                $earlyMinutes = $checkOut->diffInMinutes($workEnd);
                $isEarlyLeave = true;
            }
        }

        return [
            'check_in_time' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i') : null,
            'check_out_time' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i') : null,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'is_early_leave' => $isEarlyLeave,
            'early_minutes' => $earlyMinutes,
        ];
    }

    /**
     * @return array{0: float, 1: array}
     */
    protected function computeSystemScore(User $intern, Carbon $periodStart, Carbon $periodEnd, Carbon $asOf, $allTasks): array
    {
        $totalDays = $periodStart->diffInDays($periodEnd) + 1;

        $effectiveDate = $asOf->lt($periodStart)
            ? $periodStart->copy()
            : ($asOf->gt($periodEnd) ? $periodEnd->copy() : $asOf->copy());

        $elapsedDays = $periodStart->diffInDays($effectiveDate) + 1;

        $baseScore = min(100, ($elapsedDays / max(1, $totalDays)) * 100);

        $tasksInPeriod = $allTasks->filter(function ($t) use ($periodStart, $periodEnd) {
            if (!$t->due_date) {
                return false;
            }
            $due = Carbon::parse($t->due_date);
            return $due->gte($periodStart) && $due->lte($periodEnd);
        });

        $totalTasksInPeriod = $tasksInPeriod->count();
        $weightPerTask = $totalTasksInPeriod > 0 ? 100 / $totalTasksInPeriod : 0;

        $incompleteTasksAsOf = $tasksInPeriod
            ->whereIn('status', $this->unfinishedTaskStatuses())
            ->filter(fn ($t) => Carbon::parse($t->due_date)->endOfDay()->lte($effectiveDate->copy()->endOfDay()));

        $taskDeduction = $incompleteTasksAsOf->count() * $weightPerTask;

        $attendances = Attendance::where('user_id', $intern->id)
            ->whereBetween('date', [$periodStart->toDateString(), $effectiveDate->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());

        $lateDeduction = 0;
        $earlyDeduction = 0;

        foreach (CarbonPeriod::create($periodStart, $effectiveDate) as $day) {
            $att = $attendances->get($day->toDateString());
            if (!$att) {
                continue;
            }
            $info = $this->buildAttendanceInfo($att, $day);
            if ($info['late_minutes'] > 0) {
                $lateDeduction += floor($info['late_minutes'] / $this->lateGraceMinutes()) * $this->latePointPerStep();
            }
            if ($info['early_minutes'] > 0) {
                $earlyDeduction += floor($info['early_minutes'] / $this->lateGraceMinutes()) * $this->latePointPerStep();
            }
        }

        $score = max(0, min(100, $baseScore - $taskDeduction - $lateDeduction - $earlyDeduction));

        $breakdown = [
            'total_days' => $totalDays,
            'elapsed_days' => $elapsedDays,
            'base_score' => round($baseScore, 1),
            'total_tasks_in_period' => $totalTasksInPeriod,
            'weight_per_task' => round($weightPerTask, 2),
            'task_deduction' => round($taskDeduction, 1),
            'late_deduction' => (float) $lateDeduction,
            'early_deduction' => (float) $earlyDeduction,
        ];

        return [round($score, 1), $breakdown];
    }

    protected function countUnexcusedDays(User $intern, Carbon $start, Carbon $end): int
    {
        if ($start->gt($end)) {
            return 0;
        }

        $end = $end->copy()->min(now()->endOfDay());
        if ($start->gt($end)) {
            return 0;
        }

        $attendedDates = Attendance::where('user_id', $intern->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $approvedLeaveDates = LeaveRequest::where('user_id', $intern->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $count = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            if (!WorkScheduleResolver::isWorkingDay($day)) {
                continue;
            }
            $dateStr = $day->toDateString();
            if (in_array($dateStr, $attendedDates, true)) {
                continue;
            }
            if (in_array($dateStr, $approvedLeaveDates, true)) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    protected function computeLeaveSummary(User $intern, Carbon $start, Carbon $end, int $unexcusedCount): array
    {
        if ($start->gt($end)) {
            return ['sakit' => 0, 'acara_keluarga' => 0, 'tanpa_keterangan' => $unexcusedCount];
        }

        $approved = LeaveRequest::where('user_id', $intern->id)
            ->where('status', 'approved')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return [
            'sakit' => $approved->where('reason_type', 'sakit')->count(),
            'acara_keluarga' => $approved->where('reason_type', 'acara_keluarga')->count(),
            'tanpa_keterangan' => $unexcusedCount,
        ];
    }

    /**
     * Riwayat nilai sistem hari-per-hari, dari awal periode magang s.d.
     * tanggal yang dipilih.
     */
    protected function buildDailyHistory(User $intern, Carbon $asOf): array
    {
        $periodStart = Carbon::parse($intern->tanggal_mulai)->startOfDay();
        $periodEnd = Carbon::parse($intern->tanggal_selesai)->startOfDay();
        $effectiveEnd = $asOf->gt($periodEnd) ? $periodEnd->copy() : $asOf->copy();

        if ($periodStart->gt($effectiveEnd)) {
            return [];
        }

        $allTasks = Task::where('user_id', $intern->id)->get();

        $attendances = Attendance::where('user_id', $intern->id)
            ->whereBetween('date', [$periodStart->toDateString(), $effectiveEnd->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->date)->toDateString());

        $history = [];

        foreach (CarbonPeriod::create($periodStart, $effectiveEnd) as $day) {
            $dateStr = $day->toDateString();
            $att = $attendances->get($dateStr);
            $attInfo = $this->buildAttendanceInfo($att, $day);

            $tasksDueToday = $allTasks->filter(
                fn ($t) => $t->due_date && Carbon::parse($t->due_date)->isSameDay($day)
            );

            [$score] = $this->computeSystemScore($intern, $periodStart, $periodEnd, $day, $allTasks);

            $history[] = [
                'date' => $dateStr,
                'check_in_time' => $attInfo['check_in_time'],
                'check_out_time' => $attInfo['check_out_time'],
                'is_late' => $attInfo['is_late'],
                'late_minutes' => $attInfo['late_minutes'],
                'is_early_leave' => $attInfo['is_early_leave'],
                'early_minutes' => $attInfo['early_minutes'],
                'is_working_day' => WorkScheduleResolver::isWorkingDay($day),
                'tasks_due' => $tasksDueToday->map(fn ($t) => [
                    'title' => $t->title,
                    'status' => $t->status,
                ])->values(),
                'cumulative_score' => $score,
            ];
        }

        return array_reverse($history);
    }
}