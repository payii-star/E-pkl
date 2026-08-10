<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InternController extends Controller
{
   public function estimation(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'message' => 'User belum terautentikasi.',
        ], 401);
    }

    $tanggalMulai = $user->tanggal_mulai;
    $tanggalSelesai = $user->tanggal_selesai;

    /*
    |--------------------------------------------------------------------------
    | Data periode magang belum lengkap
    |--------------------------------------------------------------------------
    |
    | Jangan return 404.
    | Endpoint tetap berhasil dengan HTTP 200.
    |
    */

    if (!$tanggalMulai || !$tanggalSelesai) {

        return response()->json([
            'data' => [
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,

                'total_hari' => 0,
                'hari_berjalan' => 0,
                'hari_tersisa' => 0,

                'progress' => 0,

                'status' => 'incomplete',

                'message' =>
                    'Data periode magang belum lengkap.',
            ],
        ], 200);
    }

    try {

        $mulai = \Carbon\Carbon::parse(
            $tanggalMulai
        )->startOfDay();

        $selesai = \Carbon\Carbon::parse(
            $tanggalSelesai
        )->startOfDay();

        $hariIni = now()->startOfDay();

        /*
        |--------------------------------------------------------------------------
        | Validasi tanggal
        |--------------------------------------------------------------------------
        */

        if ($selesai->lt($mulai)) {

            return response()->json([
                'data' => [
                    'tanggal_mulai' => $tanggalMulai,
                    'tanggal_selesai' => $tanggalSelesai,

                    'total_hari' => 0,
                    'hari_berjalan' => 0,
                    'hari_tersisa' => 0,

                    'progress' => 0,

                    'status' => 'invalid',

                    'message' =>
                        'Tanggal selesai magang tidak boleh lebih awal dari tanggal mulai.',
                ],
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | Hitung total hari
        |--------------------------------------------------------------------------
        */

        $totalHari =
            $mulai->diffInDays($selesai) + 1;

        /*
        |--------------------------------------------------------------------------
        | Hitung hari berjalan
        |--------------------------------------------------------------------------
        */

        if ($hariIni->lt($mulai)) {

            $hariBerjalan = 0;

        } elseif ($hariIni->gt($selesai)) {

            $hariBerjalan = $totalHari;

        } else {

            $hariBerjalan =
                $mulai->diffInDays($hariIni) + 1;
        }

        /*
        |--------------------------------------------------------------------------
        | Hitung hari tersisa
        |--------------------------------------------------------------------------
        */

        $hariTersisa =
            max(
                0,
                $totalHari - $hariBerjalan
            );

        /*
        |--------------------------------------------------------------------------
        | Progress
        |--------------------------------------------------------------------------
        */

        $progress =
            $totalHari > 0
                ? round(
                    ($hariBerjalan / $totalHari) * 100,
                    2
                )
                : 0;

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($hariIni->lt($mulai)) {

            $status = 'not_started';

        } elseif ($hariIni->gt($selesai)) {

            $status = 'completed';

        } else {

            $status = 'active';
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'data' => [
                'tanggal_mulai' =>
                    $mulai->format('Y-m-d'),

                'tanggal_selesai' =>
                    $selesai->format('Y-m-d'),

                'total_hari' =>
                    $totalHari,

                'hari_berjalan' =>
                    $hariBerjalan,

                'hari_tersisa' =>
                    $hariTersisa,

                'progress' =>
                    $progress,

                'status' =>
                    $status,

                'message' =>
                    null,
            ],
        ], 200);

    } catch (\Throwable $e) {

        \Log::error(
            'Intern estimation error',
            [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]
        );

        return response()->json([
            'data' => [
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,

                'total_hari' => 0,
                'hari_berjalan' => 0,
                'hari_tersisa' => 0,

                'progress' => 0,

                'status' => 'error',

                'message' =>
                    'Estimasi magang tidak dapat dihitung.',
            ],
        ], 200);
    }
}
}
