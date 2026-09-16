<?php

namespace App\Support;

use App\Models\PublicHoliday;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Menjawab pertanyaan "apakah tanggal X hari kerja untuk user Y?" dan
 * "jam berapa jam kerja resmi di tanggal X untuk user Y?", berdasarkan
 * tabel work_schedules (jadwal mingguan per hari, bisa per user) dan
 * public_holidays (tanggal merah, override jadi libur apa pun harinya).
 *
 * Urutan prioritas penentuan jadwal:
 *   1. Tanggal merah di public_holidays  -> langsung libur
 *   2. Jadwal milik user itu sendiri     -> work_schedules.user_id = $userId
 *   3. Jadwal default                    -> work_schedules.user_id = null
 *   4. Fallback terakhir                 -> Sabtu/Minggu libur, sisanya kerja
 *
 * Dipakai oleh AttendanceController (blokir check-in/check-out saat libur)
 * dan oleh trait ComputesInternAssessment (fitur Nilai Sistem), supaya
 * keduanya konsisten pakai jadwal yang sama, bukan hardcode.
 *
 * Query di-cache secara static per request (bukan lintas request) biar
 * nggak query berkali-kali dalam 1 request yang sama. Admin yang mengubah
 * jadwal wajib memanggil clearCache() (sudah dilakukan oleh
 * AdminWorkScheduleController).
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

    /**
     * @param int|null $userId null = pakai jadwal default (global)
     */
    public static function isWorkingDay(Carbon $date, ?int $userId = null): bool
    {
        if (self::isHoliday($date)) {
            return false;
        }

        $schedule = self::scheduleFor($date, $userId);

        return $schedule ? (bool) $schedule->is_working_day : !$date->isWeekend();
    }

    public static function isHoliday(Carbon $date): bool
    {
        return self::holidays()->contains(
            fn ($h) => Carbon::parse($h->date)->isSameDay($date)
        );
    }

    /**
     * Label tanggal merah (kolom public_holidays.label), buat ditampilkan
     * di pesan error. Return null kalau tanggal itu bukan tanggal merah.
     */
    public static function holidayLabel(Carbon $date): ?string
    {
        $holiday = self::holidays()->first(
            fn ($h) => Carbon::parse($h->date)->isSameDay($date)
        );

        return $holiday?->label;
    }

    /**
     * Alasan kenapa tanggal ini bukan hari kerja, siap dipakai langsung
     * sebagai pesan API. Return null kalau tanggal itu memang hari kerja.
     */
    public static function reasonNotWorking(Carbon $date, ?int $userId = null): ?string
    {
        if (self::isHoliday($date)) {
            $label = self::holidayLabel($date);

            return $label
                ? "Hari ini libur ({$label}), absensi tidak tersedia."
                : 'Hari ini tanggal merah, absensi tidak tersedia.';
        }

        if (!self::isWorkingDay($date, $userId)) {
            return 'Hari ini bukan hari kerja sesuai jadwal kamu, absensi tidak tersedia.';
        }

        return null;
    }

    /**
     * Jam kerja resmi pada tanggal tsb.
     *
    * @return array{start: string, end: string, min_check_in: string}
     */
    public static function hoursFor(Carbon $date, ?int $userId = null): array
    {
        $schedule = self::scheduleFor($date, $userId);

        return [
            'start' => $schedule->start_time ?? '08:00:00',
            'end' => $schedule->end_time ?? '16:00:00',
            'min_check_in' => $schedule->min_check_in_time ?? '00:00:00',
        ];
    }

    /**
     * Hitung telat dan pulang cepat berdasarkan jadwal pada tanggal tertentu.
     * min_check_in_time disimpan sebagai durasi toleransi dalam format HH:MM.
     */
    public static function attendanceMetrics(
        ?string $checkInTime,
        ?string $checkOutTime,
        Carbon $date,
        ?int $userId = null
    ): array {
        $hours = self::hoursFor($date, $userId);
        $workStart = self::minutesFromTime($hours['start']);
        $workEnd = self::minutesFromTime($hours['end']);
        $tolerance = self::minutesFromTime($hours['min_check_in']);

        $lateMinutes = 0;
        if ($checkInTime) {
            $lateMinutes = max(0, self::minutesFromTime($checkInTime) - $workStart - $tolerance);
        }

        $earlyLeaveMinutes = 0;
        if ($checkOutTime) {
            $earlyLeaveMinutes = max(0, $workEnd - self::minutesFromTime($checkOutTime));
        }

        return [
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
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

    private static function scheduleFor(Carbon $date, ?int $userId = null): ?WorkSchedule
    {
        $day = self::DAY_MAP[$date->dayOfWeek];
        $schedules = self::schedules();

        if ($userId !== null) {
            $dated = $schedules->first(
                fn ($s) => (int) $s->user_id === (int) $userId
                    && $s->date
                    && Carbon::parse($s->date)->isSameDay($date)
            );

            if ($dated) {
                return $dated;
            }

            $own = $schedules->first(
                fn ($s) => $s->day === $day
                    && !$s->date
                    && (int) $s->user_id === (int) $userId
            );

            if ($own) {
                return $own;
            }
        }

        return $schedules->first(
            fn ($s) => $s->day === $day && !$s->date && $s->user_id === null
        );
    }

    private static function schedules(): Collection
    {
        return self::$scheduleCache ??= WorkSchedule::all();
    }

    private static function holidays(): Collection
    {
        return self::$holidayCache ??= PublicHoliday::all();
    }

    private static function minutesFromTime(string $time): int
    {
        [$hours, $minutes] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return ($hours * 60) + $minutes;
    }
}