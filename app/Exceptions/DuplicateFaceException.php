<?php

namespace App\Exceptions;

use Exception;

/**
 * Dilempar saat descriptor wajah yang didaftarkan cocok dengan
 * wajah user lain yang sudah terdaftar. Menangkap exception ini
 * di luar DB::transaction() memastikan seluruh proses (termasuk
 * pembuatan user) di-rollback bersama.
 */
class DuplicateFaceException extends Exception
{
    //
}