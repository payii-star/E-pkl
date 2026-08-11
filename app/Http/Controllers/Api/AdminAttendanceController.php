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
     * GET /admin/attendance/interns?month=YYYY-MM
     * Daftar peserta magang beserta ringkasan jumlah hadir di bulan yang dipilih.
     */
    public function interns(Request $request)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $interns = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['hr-admin', 'atasan']);
            })
            ->withCount(['attendances as total_hadir_bulan_ini' => function ($query) use ($start, $end) {
                $query->whereBetween('date', [$start, $end])
                    ->whereIn('status', ['hadir', 'hadir_belum_checkout']);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'intern_id'             => $user->id,
                    'name'                  => $user->name,
                    'institusi_asal'        => $user->asal_instansi,
                    'posisi'                => $user->posisi,
                    'photo'                 => $user->photo,
                    'total_hadir_bulan_ini' => $user->total_hadir_bulan_ini,
                ];
            });

        return response()->json(['data' => $interns]);
    }

    /**
     * GET /admin/attendance/{intern}?month=YYYY-MM
     * Rincian absensi harian 1 peserta magang untuk 1 bulan penuh.
     */
    public function recap(Request $request, User $intern)
    {
        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end   = $start->copy()->endOfMonth();
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

            // Hari kerja dihitung dari awal bulan s/d hari ini saja (bukan tanggal yang belum terjadi)
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
            'month' => $month,
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