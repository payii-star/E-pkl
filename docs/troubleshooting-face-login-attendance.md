# Troubleshooting Face Login Attendance Error

Dokumen ini menjelaskan perbaikan yang perlu dilakukan jika proses login dengan wajah menampilkan error seperti berikut:

```text
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'date' in 'where clause'
```

atau:

```text
SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value: '11:16:36' for column `attendances`.`check_in_time`
```

## Penyebab Utama

Masalah ini biasanya muncul karena skema database lokal berbeda dengan skema yang dipakai code terbaru.

Kasus yang ditemukan:

- Code terbaru memakai kolom `date`, `check_in_time`, `check_out_time`, `check_in_photo`, `check_out_photo`, dan `location`.
- Database lama masih memakai nama kolom seperti `tanggal`, `jam_masuk`, `jam_keluar`, `foto_masuk`, `foto_keluar`, dan `lokasi_masuk`.
- Pada beberapa database, kolom `check_in_time` dan `check_out_time` sudah ada, tetapi tipenya masih `datetime`, padahal aplikasi mengirim nilai jam saja seperti `11:16:36`.

## Gejala Yang Bisa Dicek

Jika error muncul, biasanya query yang gagal terlihat seperti ini:

```sql
select * from `attendances` where `user_id` = 4 and `date` = 2026-08-11 limit 1
```

Atau saat insert:

```sql
insert into `attendances` (`user_id`, `date`, `check_in_time`, `status`, `location`, `updated_at`, `created_at`)
values (4, 2026-08-11 00:00:00, 11:16:36, hadir, Face Recognition Login, ...)
```

Jika `check_in_time` bertipe `datetime`, MySQL akan menolak nilai jam saja.

## File Yang Harus Disamakan

### Backend

- [app/Http/Controllers/Api/FaceController.php](../app/Http/Controllers/Api/FaceController.php)
- [app/Http/Controllers/Api/AttendanceController.php](../app/Http/Controllers/Api/AttendanceController.php)
- [app/Http/Controllers/Api/AdminAttendanceController.php](../app/Http/Controllers/Api/AdminAttendanceController.php)
- [app/Models/Attendance.php](../app/Models/Attendance.php)

### Migration

- [database/migrations/2026_08_04_032830_create_attendances_table.php](../database/migrations/2026_08_04_032830_create_attendances_table.php)
- [database/migrations/2026_08_11_000200_normalize_legacy_attendances_columns.php](../database/migrations/2026_08_11_000200_normalize_legacy_attendances_columns.php)
- [database/migrations/2026_08_11_000300_fix_attendances_time_column_types.php](../database/migrations/2026_08_11_000300_fix_attendances_time_column_types.php)

## Apa Yang Diubah

### 1. Kolom attendance dinormalisasi

Kolom lama di-rename ke schema baru:

- `tanggal` -> `date`
- `jam_masuk` -> `check_in_time`
- `jam_keluar` -> `check_out_time`
- `foto_masuk` -> `check_in_photo`
- `foto_keluar` -> `check_out_photo`
- `lokasi_masuk` -> `location`

### 2. Tipe waktu disamakan

Kolom:

- `check_in_time`
- `check_out_time`

harus bertipe `TIME`, bukan `DATETIME`.

### 3. Query face login tetap memakai schema baru

Face login tetap menyimpan check-in otomatis dengan field:

- `date` = tanggal hari ini
- `check_in_time` = jam saat login
- `status` = `hadir`
- `location` = `Face Recognition Login`

## Langkah Perbaikan Untuk Teman Yang Mengalami Error Sama

### A. Cek struktur tabel attendance

Jalankan:

```bash
php artisan tinker
```

Lalu:

```php
dump(DB::select('SHOW COLUMNS FROM attendances'));
```

Pastikan hasilnya mengandung:

- `date` bertipe `date`
- `check_in_time` bertipe `time`
- `check_out_time` bertipe `time`

### B. Jalankan migration

```bash
php artisan migrate
```

Migration yang penting adalah dua file ini:

- `2026_08_11_000200_normalize_legacy_attendances_columns.php`
- `2026_08_11_000300_fix_attendances_time_column_types.php`

### C. Bersihkan cache bila perlu

Jika error masih muncul setelah migrate, jalankan:

```bash
php artisan optimize:clear
```

Lalu refresh aplikasi dan ulang login wajah.

## Verifikasi Setelah Perbaikan

Pastikan query berikut tidak lagi error:

- cek attendance hari ini berdasarkan `user_id` dan `date`
- insert/update attendance dengan `check_in_time` dari `now()->toTimeString()`

Tes aman yang dipakai saat debugging:

```php
Attendance::updateOrCreate(
    ['user_id' => 4, 'date' => now()->toDateString()],
    ['check_in_time' => now()->toTimeString(), 'status' => 'hadir', 'location' => 'Face Recognition Login']
);
```

Jika perintah di atas sukses, maka login wajah juga seharusnya aman.

## Catatan Penting

- Jangan hanya menambah kolom baru tanpa mengecek tipe kolom lama.
- Jika database teman masih fresh dan belum pernah punya schema lama, error ini mungkin tidak muncul.
- Jika database dibuat dari migrasi lama, kemungkinan besar perlu normalisasi kolom seperti di atas.
