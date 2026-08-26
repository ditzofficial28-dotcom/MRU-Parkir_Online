# Deskripsi Program — Aplikasi Parkir

## 1. Tujuan
Aplikasi ini digunakan untuk mengelola operasional parkir kendaraan, mencakup
pencatatan kendaraan masuk/keluar, perhitungan biaya, cetak struk, serta rekap
pendapatan, dengan tiga level pengguna sesuai hak akses masing-masing.

## 2. Teknologi
- Bahasa: PHP (native, PDO untuk akses database)
- Basis Data: MySQL/MariaDB
- Front-end: HTML + CSS sederhana (assets/style.css)
- Arsitektur: Prosedural dengan pemisahan modul per folder (admin/petugas/owner),
  fungsi umum dikumpulkan pada `includes/functions.php` agar tidak duplikasi kode.

## 3. Struktur Folder
```
aplikasi-parkir/
├── config/koneksi.php        -> koneksi database (PDO)
├── includes/
│   ├── auth.php               -> session guard & pembatasan akses per role
│   ├── functions.php          -> fungsi/prosedur inti (hitung biaya, log, dsb)
│   ├── header.php / footer.php-> tampilan bersama
├── admin/                     -> modul khusus role admin
├── petugas/                   -> modul khusus role petugas
├── owner/                     -> modul khusus role owner
├── public/                    -> frontend publik (pengunjung, tanpa login)
├── assets/style.css
├── database/parkir.sql
├── login.php / logout.php / index.php
```

## 4. Hak Akses per Role (sesuai Tabel Fitur pada soal)

| Fitur | Admin | Petugas | Owner |
|---|:---:|:---:|:---:|
| Login / Logout | V | V | V |
| CRUD User | V | | |
| CRUD Tarif Parkir | V | | |
| CRUD Area Parkir | V | | |
| CRUD Kendaraan | V | | |
| Akses Log Aktivitas | V | | |
| Cetak Struk Parkir | | V | |
| Transaksi | | V | |
| Rekap Transaksi | | | V |

Pembatasan akses diimplementasikan melalui fungsi `cekRole()` pada
`includes/auth.php`, dipanggil di baris pertama setiap halaman modul.

## 5. Alur Bisnis Utama
1. **Kendaraan Masuk**: petugas input plat nomor & jenis kendaraan → sistem
   cek kapasitas area → simpan/ambil data kendaraan → buat transaksi status `masuk`.
2. **Kendaraan Keluar**: petugas pilih transaksi aktif → sistem hitung durasi
   (pembulatan ke atas per jam) dan biaya (`durasi × tarif/jam`) → update status
   `keluar` → cetak struk.
3. **Rekap**: owner memilih rentang tanggal → sistem menjumlahkan `biaya_total`
   transaksi berstatus `keluar` pada rentang tersebut.

## 6. Keamanan & Best Practice yang Diterapkan
- Semua query menggunakan **prepared statement** (mencegah SQL Injection).
- Password disimpan dengan **password_hash (bcrypt)**, diverifikasi dengan `password_verify()`.
- Input pengguna disaring lewat `amankanInput()` (mencegah XSS).
- Query memakai `LIMIT` pada data besar (kendaraan, log, rekap) agar halaman tetap cepat.
- Logika berulang dipisah menjadi fungsi/prosedur (`hitungBiaya`, `hitungDurasiJam`,
  `cekKapasitasArea`, `ubahTerisiArea`, `catatLog`, dst) — bukan duplikasi kode.
- Setiap aksi penting (login, logout, CRUD, transaksi) dicatat ke `tb_log_aktivitas`.

## 7. Pembaruan Tampilan & Analitik (v2)
- UI dirombak menjadi dashboard bergaya enterprise: sidebar navigasi tetap,
  topbar, dan sistem desain konsisten (`assets/style.css`) dengan palet
  navy + amber, tipografi Sora/Inter/JetBrains Mono, serta transisi halus.
- Dashboard tiap role kini menampilkan data nyata dari database:
  - **Admin**: kartu statistik (user aktif, kendaraan terdaftar, transaksi
    hari ini, kendaraan sedang parkir), progress bar okupansi per area,
    dan feed aktivitas terbaru.
  - **Petugas**: statistik tugas harian pribadi dan daftar kendaraan
    masuk terbaru.
  - **Owner**: grafik tren pendapatan 7 hari (Chart.js) memakai VIEW
    `v_pendapatan_harian`, statistik bulan berjalan, dan area paling ramai.
- Database ditambah index (`status`, `waktu_masuk`, `waktu_keluar`,
  `plat_nomor`, `waktu_aktivitas`) untuk performa query dashboard, serta
  view `v_pendapatan_harian` untuk agregasi pendapatan harian.

## 8. Frontend Publik (v3)
- Ditambahkan situs publik di folder `public/` untuk pengunjung/pemilik
  kendaraan (bukan staf), tanpa perlu login, membaca data yang sama
  dengan yang dikelola admin (read-only):
  - **Beranda** (`public/index.php`) — hero, statistik live (jumlah area,
    sisa slot, tarif termurah), fitur, preview tarif.
  - **Ketersediaan Slot** (`public/ketersediaan.php`) — okupansi tiap
    area parkir real-time dari `tb_area_parkir`.
  - **Tarif Parkir** (`public/tarif.php`) — daftar tarif dari `tb_tarif`.
  - **Lacak Kendaraan** (`public/lacak.php`) — pencarian berdasarkan plat
    nomor ke `tb_transaksi`, menghitung estimasi biaya berjalan dengan
    fungsi yang sama dipakai backend petugas (`hitungDurasiJam`,
    `hitungBiaya`) bila kendaraan masih berstatus 'masuk'.
- Navbar publik menyediakan tombol "Masuk Staf" menuju `login.php` untuk
  admin/petugas/owner. Tidak ada tabel atau role baru — situs publik
  murni mengikuti struktur data yang sudah dikelola backend admin.

## 9. Rebrand GoParkir & Restrukturisasi Navigasi Admin (v3)
- Seluruh aplikasi (staf & publik) di-rebrand menjadi **GoParkir** dengan
  palet warna teal/hijau (`#10B981`) + navy, mengikuti mockup desain yang
  diberikan.
- Navigasi admin direstrukturisasi menjadi 5 tab (mengikuti mockup):
  **Panel Administrator** (`dashboard.php` — kartu lantai, sesi aktif,
  summary analytics, akses cepat, spot list), **Manajemen Parkir**
  (`parkir.php` — hub kartu area + akses ke CRUD Area/Tarif/Kendaraan),
  **Analitik Kinerja** (`analitik.php` — grafik batang okupansi per area
  dengan Chart.js), **Manajemen Pengguna** (`user.php`), dan
  **Laporan & Audit** (`log.php`). Semua tab memakai data dan fungsi CRUD
  yang sama seperti sebelumnya (prepared statement, tidak ada perubahan
  skema database) — hanya presentasinya yang direstrukturisasi.
- Beranda publik dirombak mengikuti mockup: hero dengan ilustrasi SVG
  gerbang parkir, kartu "Cari Lokasi Parkir" mengambang yang terhubung
  ke halaman Ketersediaan/Lacak, 4 kartu fitur berwarna, 5 langkah
  "Cara Kerja GoParkir", dan panel promosi aplikasi mobile (mockup UI,
  tanpa backend aplikasi mobile terpisah).
