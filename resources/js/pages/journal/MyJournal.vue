<template>
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h2>Jurnal Harian</h2>
            </div>
        </div>
        <div class="card-body">
            <p class="text-gray-600">
                Struktur awal form pengisian jurnal harian. Nanti di sini
                akan ada: field tanggal, breakdown aktivitas (list task +
                durasi), dan tombol kirim untuk approval atasan.
            </p>
            <div class="mb-5">
                <label class="form-label">Tanggal</label>
                <input type="date" class="form-control" disabled />
            </div>
            <div class="mb-5">
                <label class="form-label">Aktivitas Hari Ini</label>
                <textarea
                    class="form-control"
                    rows="4"
                    disabled
                    placeholder="Breakdown aktivitas harian akan diisi di sini"
                ></textarea>
            </div>
            <button class="btn btn-primary" disabled>Kirim Jurnal</button>
        </div>
    </div>
</template>

<script setup lang="ts">
// TODO: integrasi POST /api/journals (dikerjakan setelah backend siap)
</script>
