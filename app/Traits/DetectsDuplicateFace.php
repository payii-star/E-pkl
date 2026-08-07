<?php

namespace App\Traits;

use App\Exceptions\DuplicateFaceException;
use App\Models\FaceProfile;

trait DetectsDuplicateFace
{
    // Jarak Euclidean maksimum antar descriptor supaya dianggap "wajah yang sama".
    // Semakin kecil = semakin ketat. 0.5 adalah nilai umum untuk face-api.js.
    protected float $duplicateThreshold = 0.5;

    /**
     * Hitung descriptor rata-rata (element-wise mean) dari beberapa sample descriptor.
     */
    protected function averageDescriptors(array $descriptors): array
    {
        $count  = count($descriptors);
        $length = count($descriptors[0]);
        $sum    = array_fill(0, $length, 0.0);

        foreach ($descriptors as $descriptor) {
            foreach ($descriptor as $i => $value) {
                $sum[$i] += $value;
            }
        }

        return array_map(fn ($v) => $v / $count, $sum);
    }

    /**
     * Hitung jarak Euclidean antar dua descriptor 128 dimensi.
     */
    protected function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        foreach ($a as $i => $value) {
            $sum += ($value - $b[$i]) ** 2;
        }

        return sqrt($sum);
    }

    /**
     * Pastikan descriptor ini belum dipakai user lain.
     * Melempar DuplicateFaceException jika ditemukan kecocokan.
     *
     * @throws DuplicateFaceException
     */
    protected function assertFaceNotDuplicate(array $descriptor, ?int $excludeUserId = null): void
    {
        $query = FaceProfile::query();
        if ($excludeUserId !== null) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        foreach ($query->get() as $profile) {
            $distance = $this->euclideanDistance($descriptor, $profile->descriptor);
            if ($distance < $this->duplicateThreshold) {
                throw new DuplicateFaceException(
                    'Wajah ini sudah terdaftar pada akun lain. Satu wajah hanya bisa dipakai untuk satu akun.'
                );
            }
        }
    }
}