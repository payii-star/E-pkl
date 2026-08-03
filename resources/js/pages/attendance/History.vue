<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Riwayat Absensi</h2>
            </div>
        </div>
        <div class="card-body">
            <p class="text-gray-600">
                Struktur awal halaman riwayat absensi. Nanti di sini akan ada
                tabel berisi: Tanggal, Jam Masuk, Jam Keluar, Status, dan
                filter berdasarkan bulan/rentang tanggal.
            </p>
            <table class="table table-row-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Keluar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center text-gray-500">
                            Belum ada data (API belum terhubung)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
// TODO: fetch data dari GET /api/attendances (dikerjakan setelah backend siap)
</script>
