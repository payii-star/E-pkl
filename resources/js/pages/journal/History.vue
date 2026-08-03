<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Riwayat Jurnal</h2>
            </div>
        </div>
        <div class="card-body">
            <p class="text-gray-600">
                Struktur awal halaman riwayat jurnal harian milik user, beserta
                status approval-nya (Pending / Disetujui / Ditolak).
            </p>
            <table class="table table-row-bordered">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Ringkasan Aktivitas</th>
                        <th>Status Approval</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3" class="text-center text-gray-500">
                            Belum ada data (API belum terhubung)
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
// TODO: fetch data dari GET /api/journals/history
</script>
