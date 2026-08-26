# Dokumentasi Fungsi & Prosedur

Format: **Nama** — Input → Proses → Output. Lokasi file disertakan.

## includes/functions.php

### 1. `catatLog(PDO $pdo, int $id_user, string $aktivitas): void`
- **Input**: koneksi PDO, id user pelaku, teks aktivitas
- **Proses**: INSERT satu baris ke `tb_log_aktivitas` dengan waktu sekarang
- **Output**: tidak ada return value (prosedur), efek samping berupa baris log baru

### 2. `hitungDurasiJam(string $waktu_masuk, string $waktu_keluar): int`
- **Input**: waktu masuk & keluar (format datetime)
- **Proses**: menghitung selisih menit, dibulatkan ke atas per 60 menit
- **Output**: jumlah jam (integer, minimal 1)

### 3. `hitungBiaya(int $durasi_jam, $tarif_per_jam): float`
- **Input**: durasi (jam), tarif per jam
- **Proses**: perkalian durasi × tarif
- **Output**: total biaya (float)

### 4. `cekKapasitasArea(PDO $pdo, int $id_area): bool`
- **Input**: koneksi PDO, id area
- **Proses**: SELECT kapasitas & terisi, bandingkan nilainya
- **Output**: `true` jika area masih memiliki slot kosong

### 5. `ubahTerisiArea(PDO $pdo, int $id_area, int $delta): void`
- **Input**: koneksi PDO, id area, delta (+1 saat masuk, -1 saat keluar)
- **Proses**: UPDATE kolom `terisi`, dijaga tidak boleh negatif (`GREATEST(0, ...)`)
- **Output**: tidak ada return value, efek samping mengubah kapasitas terisi

### 6. `ambilTarifByJenis(PDO $pdo, string $jenis_kendaraan)`
- **Input**: koneksi PDO, jenis kendaraan
- **Proses**: SELECT baris tarif sesuai jenis (LIMIT 1, query efisien)
- **Output**: array data tarif, atau `false` jika tidak ditemukan

### 7. `formatRupiah($angka): string`
- **Input**: nilai numerik
- **Proses**: format ribuan dengan pemisah titik, prefix "Rp"
- **Output**: string mata uang, mis. `Rp 10.000`

### 8. `amankanInput(string $data): string`
- **Input**: teks mentah dari form
- **Proses**: `trim()` lalu `htmlspecialchars()`
- **Output**: teks yang aman ditampilkan/disimpan

## includes/auth.php

### 9. `cekLogin(): void`
- **Input**: session aktif (`$_SESSION['id_user']`)
- **Proses**: jika session belum ada, redirect ke `login.php`
- **Output**: tidak ada (prosedur kontrol alur)

### 10. `cekRole(array $rolesDiizinkan): void`
- **Input**: daftar role yang diperbolehkan mengakses halaman
- **Proses**: panggil `cekLogin()`, lalu bandingkan role session dengan daftar
- **Output**: hentikan eksekusi (403) bila role tidak sesuai

### 11. `basePath(): string`
- **Input**: `$_SERVER['SCRIPT_NAME']`
- **Proses**: deteksi apakah file dipanggil dari sub-folder (admin/petugas/owner)
- **Output**: string `"../"` atau `""` untuk keperluan link relatif

## Modul Transaksi (petugas/transaksi.php)

### 12. Proses "Kendaraan Masuk"
- **Input**: plat_nomor, jenis_kendaraan, warna, pemilik, id_area
- **Proses**: cek kapasitas area → cari/insert data kendaraan → ambil tarif
  sesuai jenis → insert baris `tb_transaksi` status `masuk` → tambah `terisi` area
- **Output**: baris transaksi baru, pesan konfirmasi

### 13. Proses "Kendaraan Keluar"
- **Input**: id_parkir
- **Proses**: ambil transaksi aktif → hitung durasi & biaya → update transaksi
  status `keluar` → kurangi `terisi` area → redirect ke halaman struk
- **Output**: transaksi ter-update, tampilan struk
