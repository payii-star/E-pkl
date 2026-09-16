<?php

namespace App\Support;

use App\Models\PublicHoliday;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menjawab pertanyaan "apakah tanggal X hari kerja?" dan "jam berapa jam
 * kerja resmi di tanggal X?", berdasarkan tabel work_schedules (jadwal
 * mingguan per hari) dan public_holidays (tanggal merah, override jadi
 * libur apa pun hari kerjanya).
 *
 * Dipakai oleh AttendanceController (kalau perlu validasi jam check-in/
 * check-out) dan oleh trait ComputesInternAssessment (fitur Nilai Sistem),
 * supaya keduanya konsisten pakai jadwal yang sama, bukan hardcode.
 *
 * Query di-cache secara static per request (bukan lintas request) biar
 * nggak query berkali-kali dalam 1 request yang sama.
 */
class WorkScheduleResolver
{
    private static ?Collection $scheduleCache = null;
    private static ?Collection $holidayCache = null;

    private const DAY_MAP = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    public static function isWorkingDay(Carbon $date): bool
    {
        if (self::isHoliday($date)) {
            return false;
        }

        $schedule = self::scheduleFor($date);

        return $schedule ? (bool) $schedule->is_working_day : !$date->isWeekend();
    }

    public static function isHoliday(Carbon $date): bool
    {
        return self::holidays()->contains(
            fn ($h) => Carbon::parse($h->date)->isSameDay($date)
        );
    }

    /**
     * @return array{start: string, end: string} format "H:i:s"
     */
    public static function hoursFor(Carbon $date): array
    {
        $schedule = self::scheduleFor($date);

        return [
            'start' => $schedule->start_time ?? '08:00:00',
            'end' => $schedule->end_time ?? '16:00:00',
        ];
    }

    /**
     * Reset cache in-memory (dipanggil setelah admin update jadwal,
     * kalau resolver ini dipakai lagi dalam request yang sama).
     */
    public static function clearCache(): void
    {
        self::$scheduleCache = null;
        self::$holidayCache = null;
    }

    private static function scheduleFor(Carbon $date): ?WorkSchedule
    {
        $day = self::DAY_MAP[$date->dayOfWeek];

        return self::schedules()->get($day);
    }

    private static function schedules(): Collection
    {
        return self::$scheduleCache ??= WorkSchedule::all()->keyBy('day');
    }

    private static function holidays(): Collection
    {
        return self::$holidayCache ??= PublicHoliday::all();
    }
}
