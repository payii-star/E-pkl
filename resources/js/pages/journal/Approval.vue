<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Approval Jurnal</h2>
            </div>
        </div>
        <div class="card-body">
            <p class="text-gray-600">
                Struktur awal halaman approval untuk atasan/supervisor.
                Nanti di sini akan ada daftar jurnal anak magang yang
                menunggu persetujuan, dengan tombol Setuju / Tolak +
                catatan.
            </p>
            <table class="table table-row-bordered">
                <thead>
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Tanggal</th>
                        <th>Ringkasan</th>
                        <th>Aksi</th>
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
// TODO: fetch GET /api/journals/pending-approval + POST /api/journals/{id}/approve|reject
// TODO: halaman ini idealnya hanya muncul di menu untuk role "Atasan/Supervisor" (guard by permission)
</script>
