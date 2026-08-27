<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    /**
     * Tentukan rentang tanggal [start, end] berdasarkan period ('week'/'month')
     * dan tanggal acuan. Dipakai bareng oleh interns() dan recap() supaya
     * konsisten satu sama lain.
     */
    private function resolveRange(string $period, ?string $date): array
    {
        if ($period === 'week') {
            $anchor = $date ? Carbon::parse($date) : now();
            $start  = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $end    = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
        } else {
            $month = $date ?? now()->format('Y-m');
            $start = Carbon::parse($month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        }

        return [$start, $end];
    }

    /**
     * GET /admin/attendance/interns?period=week|month&date=YYYY-MM-DD (week) atau &month=YYYY-MM
     * Daftar peserta magang beserta ringkasan jumlah hadir di periode yang dipilih.
     */
    public function interns(Request $request)
    {
        $period = $request->query('period', 'month');
        $refDate = $period === 'week'
            ? $request->query('date')
            : $request->query('month', now()->format('Y-m'));

        [$start, $end] = $this->resolveRange($period, $refDate);

        $interns = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan']);
            })
            ->withCount(['attendances as total_hadir_periode' => function ($query) use ($start, $end) {
                $query->whereBetween('date', [$start, $end])
                    ->whereIn('status', ['hadir', 'hadir_belum_checkout']);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'intern_id'           => $user->id,
                    'name'                => $user->name,
                    'institusi_asal'      => $user->asal_instansi,
                    'posisi'              => $user->posisi,
                    'photo'               => $user->photo,
                    'total_hadir_periode' => $user->total_hadir_periode,
                ];
            });

        return response()->json(['data' => $interns]);
    }

    /**
     * GET /admin/attendance/{intern}?period=week|month&date=YYYY-MM-DD (week) atau &month=YYYY-MM
     * Rincian absensi harian 1 peserta magang untuk 1 minggu atau 1 bulan penuh.
     */
    public function recap(Request $request, User $intern)
    {
        $period = $request->query('period', 'month');
        $refDate = $period === 'week'
            ? $request->query('date')
            : $request->query('month', now()->format('Y-m'));

        [$start, $end] = $this->resolveRange($period, $refDate);

        $today = Carbon::today();
        $dateColumn = Attendance::dateColumn();
        $checkInTimeColumn = Attendance::checkInTimeColumn();
        $checkOutTimeColumn = Attendance::checkOutTimeColumn();

        $attendances = Attendance::where('user_id', $intern->id)
            ->whereBetween($dateColumn, [$start, $end])
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->{$dateColumn})->format('Y-m-d'));

        $days = [];
        $totalHariKerja  = 0;
        $totalHadir      = 0;
        $totalTidakHadir = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey    = $date->format('Y-m-d');
            $isWeekend  = $date->isWeekend();
            $attendance = $attendances->get($dateKey);

            if ($isWeekend) {
                $status = 'libur';
            } elseif ($date->gt($today)) {
                $status = 'akan_datang';
            } elseif ($attendance) {
                $status = $attendance->{$checkOutTimeColumn} ? 'hadir' : 'hadir_belum_checkout';
            } else {
                $status = 'tidak_hadir';
            }

            // Hari kerja dihitung dari awal periode s/d hari ini saja (bukan tanggal yang belum terjadi)
            if (!$isWeekend && $date->lte($today)) {
                $totalHariKerja++;
                if ($status === 'hadir' || $status === 'hadir_belum_checkout') {
                    $totalHadir++;
                } elseif ($status === 'tidak_hadir') {
                    $totalTidakHadir++;
                }
            }

            $days[] = [
                'date'           => $dateKey,
                'is_weekend'     => $isWeekend,
                'check_in_time'  => $attendance?->{$checkInTimeColumn},
                'check_out_time' => $attendance?->{$checkOutTimeColumn},
                'status'         => $status,
            ];
        }

        $persentase = $totalHariKerja > 0
            ? round(($totalHadir / $totalHariKerja) * 100, 1)
            : 0;

        return response()->json([
            'period' => $period,
            'range' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'summary' => [
                'total_hari_kerja'     => $totalHariKerja,
                'total_hadir'          => $totalHadir,
                'total_tidak_hadir'    => $totalTidakHadir,
                'persentase_kehadiran' => $persentase,
            ],
            'days' => $days,
        ]);
    }
}