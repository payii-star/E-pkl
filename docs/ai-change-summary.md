# Ringkasan Perubahan untuk AI atau Developer Lain

## Konteks Proyek

Ini adalah aplikasi Laravel dengan frontend Vue/TypeScript untuk sistem absensi siswa/magang. Fitur yang terkait:

- Profil user dapat diedit dari halaman dashboard.
- User dapat mengajukan izin tidak masuk.
- Admin dapat menyetujui atau menolak izin.
- User dapat melakukan absen masuk dan absen pulang melalui web atau endpoint mobile.
- Data absensi menggunakan model `Attendance`.
- Data izin menggunakan model `LeaveRequest`.

## Perubahan yang Sudah Dibuat

### 1. Perbaikan Edit Profil

Masalah awal: halaman edit profil mengirim request dengan method override `PUT`, tetapi route hanya menerima `POST`, sehingga muncul error `Method Not Allowed`.

Perubahan:

- Route `/api/profile` sekarang menerima `POST`, `PUT`, dan `PATCH`.
- Field `phone` dibuat opsional agar sesuai dengan form frontend.
- Field lokasi sekolah berikut dibuat opsional:
  - `asal_instansi_address`
  - `asal_instansi_latitude`
  - `asal_instansi_longitude`
  - `asal_instansi_place_id`
- Field lokasi hanya diperbarui jika dikirim dalam request. Data lama tidak dihapus jika field tersebut tidak dikirim.

File:

- `routes/api.php`
- `app/Http/Controllers/UserController.php`
- Form frontend berada di `resources/js/pages/dashboard/profile/Index.vue`.

Endpoint:

```text
POST|PUT|PATCH /api/profile
```

Controller:

```text
UserController@updateProfile
```

### 2. User dengan Izin Disetujui Tidak Boleh Absen Masuk

Aturan bisnis:

- Hanya izin dengan status `approved` yang memblokir absensi.
- Izin `pending` dan `rejected` tidak memblokir absensi.
- Pengecekan hanya berlaku untuk tanggal hari ini.
- Tanggal hari ini dihitung menggunakan timezone aplikasi, default `Asia/Jakarta`.

Pengecekan dilakukan di backend melalui helper:

```php
private function hasApprovedLeaveToday(int $userId): bool
```

Helper tersebut mencari record `LeaveRequest` dengan kondisi:

```text
user_id = user login
 date = hari ini
 status = approved
```

Jika ditemukan, endpoint check-in mengembalikan HTTP `403` dan tidak memproses foto atau membuat absensi.

Pesan error:

```text
Kamu memiliki izin yang sudah disetujui untuk hari ini, sehingga tidak dapat absen masuk.
```

Pengecekan diterapkan ke dua endpoint:

```text
POST /api/attendances/check-in
POST /api/attendances/check-in-web
```

Endpoint pertama memakai upload file foto untuk mobile. Endpoint kedua memakai foto base64 untuk halaman web.

### 3. User dengan Izin Disetujui Dianggap Sudah Pulang

Aturan bisnis tambahan:

- Jika admin menyetujui izin pada hari ini dan user sudah memiliki jam masuk, sistem otomatis mengisi jam pulang.
- Setelah ada izin disetujui, user tidak boleh melakukan absen pulang manual.
- Berlaku untuk endpoint check-out mobile dan web.

Saat admin menyetujui izin di:

```text
LeaveRequestController@updateStatus
```

sistem:

1. Mengubah status izin menjadi `approved`.
2. Mencari absensi user pada tanggal hari ini.
3. Jika absensi memiliki jam masuk tetapi belum memiliki jam pulang, sistem mengisi jam pulang dengan waktu approval saat ini.

Pengecekan jam masuk dan jam pulang menggunakan helper kolom dari model `Attendance`, sehingga tetap kompatibel dengan kemungkinan nama kolom lama:

- `Attendance::dateColumn()`
- `Attendance::checkInTimeColumn()`
- `Attendance::checkOutTimeColumn()`

Endpoint check-out menolak request jika user memiliki izin approved hari ini.

Pesan error:

```text
Kamu memiliki izin yang sudah disetujui untuk hari ini, sehingga sudah dianggap pulang.
```

Endpoint yang dilindungi:

```text
POST /api/attendances/check-out
POST /api/attendances/check-out-web
```

HTTP response untuk penolakan adalah `403`.

### 4. Dampak pada Tampilan Frontend

Halaman check-out berada di:

```text
resources/js/pages/attendance/CheckOut.vue
```

Halaman tersebut mengambil data dari:

```text
GET /api/attendances/today
```

Jika backend sudah mengisi `check_out_time` saat izin disetujui, halaman akan menganggap absensi selesai dan tidak menampilkan alur kamera check-out.

Computed state yang digunakan frontend:

- `alreadyDone`: true jika ada `check_in_time` dan `check_out_time`.
- `statusLabel`: menampilkan `Belum Absen`, `Sudah Masuk`, atau `Selesai`.

## File Utama yang Berkaitan

- `app/Http/Controllers/Api/AttendanceController.php`
  - Check-in mobile dan web.
  - Check-out mobile dan web.
  - Helper pengecekan izin approved hari ini.
- `app/Http/Controllers/Api/LeaveRequestController.php`
  - Approval/rejection izin.
  - Pengisian otomatis jam pulang saat izin disetujui.
- `app/Models/LeaveRequest.php`
  - Status izin: `pending`, `approved`, `rejected`.
  - Kolom tanggal: `date`.
- `app/Models/Attendance.php`
  - Helper kompatibilitas nama kolom absensi.
- `routes/api.php`
  - Pendaftaran endpoint profile, attendance, dan leave request.
- `resources/js/pages/attendance/CheckIn.vue`
  - UI absen masuk web.
- `resources/js/pages/attendance/CheckOut.vue`
  - UI absen pulang web.
- `resources/js/pages/dashboard/profile/Index.vue`
  - UI edit profil.

## Urutan Alur Approval Izin

### Skenario A: Izin Disetujui Sebelum User Absen

1. User mengajukan izin.
2. Admin mengubah status menjadi `approved`.
3. User mencoba absen masuk.
4. Backend menemukan izin approved hari ini.
5. Backend mengembalikan `403`.
6. User tidak dapat absen masuk maupun absen pulang.

### Skenario B: User Sudah Absen Masuk, Lalu Izin Disetujui

1. User melakukan absen masuk.
2. Admin menyetujui izin pada hari yang sama.
3. Backend mencari absensi hari ini.
4. Jika belum ada jam pulang, backend mengisi jam pulang otomatis.
5. User dianggap sudah pulang.
6. User tidak dapat melakukan absen pulang manual.

### Skenario C: Izin Masih Pending atau Ditolak

1. User memiliki izin `pending` atau `rejected`.
2. Pengecekan `hasApprovedLeaveToday()` menghasilkan false.
3. User tetap dapat mengikuti alur absensi normal, selama belum absen masuk atau pulang.

## Validasi yang Sudah Dilakukan

Perintah berikut sudah berhasil dijalankan:

```bash
php -l app/Http/Controllers/Api/AttendanceController.php
php -l app/Http/Controllers/Api/LeaveRequestController.php
php artisan route:list --path=api/attendances
```

Hasil:

- Tidak ada error sintaks PHP.
- Endpoint check-in dan check-out tetap terdaftar.
- Pemeriksaan editor juga tidak menemukan error pada controller terkait.

## Catatan untuk Perubahan Berikutnya

- Jangan hanya mengandalkan pembatasan di Vue. Semua aturan absensi harus tetap dicek di backend karena request dapat dimanipulasi.
- Jika menambah endpoint absensi baru, gunakan helper `hasApprovedLeaveToday()` untuk check-in dan check-out sesuai aturan bisnis.
- Jika mengubah timezone, gunakan `config('app.timezone', 'Asia/Jakarta')` secara konsisten.
- Belum ada automated feature test khusus untuk skenario izin approved dan auto-checkout. Test tersebut sebaiknya ditambahkan bila fitur akan dikembangkan lebih lanjut.
- Pastikan approval izin hanya bisa dilakukan oleh role admin/HR melalui middleware/permission yang sudah berlaku di route admin.
