# Laporan Evaluasi Singkat — Aplikasi Parkir

## 1. Fitur yang Sudah Berjalan dengan Baik
- Login & logout untuk 3 role (admin, petugas, owner), dengan pembatasan akses
  per halaman melalui `cekRole()`.
- CRUD User, Tarif Parkir, Area Parkir, dan Kendaraan (khusus admin).
- Akses Log Aktivitas (khusus admin), mencatat seluruh aksi penting user.
- Transaksi kendaraan masuk & keluar (khusus petugas), termasuk validasi
  kapasitas area dan perhitungan biaya otomatis.
- Cetak struk parkir (khusus petugas) dengan tampilan siap cetak.
- Rekap transaksi sesuai rentang waktu (khusus owner), lengkap dengan total
  pendapatan dan jumlah transaksi.
- Keamanan dasar: prepared statement, password hashing (bcrypt), pembersihan
  input (anti-XSS).

## 2. Bug / Keterbatasan yang Belum Diperbaiki
- Belum ada validasi format plat nomor (mis. pola huruf-angka baku Indonesia).
- Belum ada fitur "lupa password" / reset password mandiri oleh user.
- Rekap transaksi belum dapat diekspor ke Excel/PDF, saat ini hanya tampilan
  tabel di halaman web.
- Belum ada pagination pada tabel dengan data sangat besar (saat ini dibatasi
  dengan `LIMIT` tetap, mis. 100–500 baris) — untuk skala produksi sebaiknya
  ditambahkan pagination dinamis.
- Validasi sisi client (JavaScript) masih minim; validasi utama masih
  mengandalkan sisi server.

## 3. Rencana Pengembangan Berikutnya
- Menambahkan dashboard statistik (grafik pendapatan harian/bulanan) untuk owner.
- Menambahkan fitur cetak/ekspor rekap ke PDF dan Excel.
- Menambahkan notifikasi saat area parkir mendekati kapasitas penuh.
- Menambahkan riwayat kendaraan per plat nomor (histori kunjungan).
- Meningkatkan validasi form di sisi client agar pengalaman pengguna lebih baik.
- Menambahkan fitur pencarian & filter pada tabel kendaraan dan log aktivitas.
