# Flowchart & Pseudocode

## A. Proses Login

**Deskripsi**: User memasukkan username & password. Sistem memvalidasi ke tabel
`tb_user`, memverifikasi password ter-hash, lalu mengarahkan ke dashboard sesuai
role. Setiap login berhasil dicatat ke log aktivitas.

**Alur (flowchart teks)**
```
Mulai
  → Tampilkan form login
  → Input username, password
  → Ambil user dari tb_user WHERE username = ? AND status_aktif = 1
  → Jika user tidak ditemukan:
        Tampilkan pesan error → kembali ke form login
  → Jika user ditemukan:
        Verifikasi password dengan password_verify()
        Jika salah:
            Tampilkan pesan error → kembali ke form login
        Jika benar:
            Simpan id_user, nama, role ke session
            Catat log "Login ke sistem"
            Redirect sesuai role (admin/petugas/owner)
Selesai
```

**Pseudocode**
```
BACA username, password
user = QUERY("SELECT * FROM tb_user WHERE username=? AND status_aktif=1", username)
JIKA user KOSONG ATAU !password_verify(password, user.password) MAKA
    TAMPIL "Username atau password salah"
LAINNYA
    SET session.id_user = user.id_user
    SET session.role = user.role
    CATAT_LOG(user.id_user, "Login ke sistem")
    JIKA role == "admin" MAKA REDIRECT admin/dashboard.php
    LAINNYA JIKA role == "petugas" MAKA REDIRECT petugas/dashboard.php
    LAINNYA REDIRECT owner/dashboard.php
```

---

## B. Proses Transaksi (Kendaraan Masuk & Keluar)

**Deskripsi**: Petugas mencatat kendaraan masuk dengan memilih area parkir yang
masih tersedia. Saat kendaraan keluar, sistem menghitung durasi & biaya secara
otomatis lalu mengurangi jumlah kendaraan pada area tersebut.

**Alur — Kendaraan Masuk**
```
Mulai
  → Input plat_nomor, jenis_kendaraan, id_area
  → Cek kapasitas area (terisi < kapasitas)?
        Tidak → Tampilkan "Area penuh" → Selesai
        Ya  → Lanjut
  → Cari data kendaraan berdasarkan plat_nomor
        Tidak ada → Insert kendaraan baru
  → Ambil tarif sesuai jenis_kendaraan
  → Insert tb_transaksi (status = 'masuk', waktu_masuk = sekarang)
  → Tambah 1 ke kolom terisi pada area
  → Catat log aktivitas
Selesai
```

**Alur — Kendaraan Keluar**
```
Mulai
  → Input id_parkir (dipilih dari daftar kendaraan yang masih di dalam)
  → Ambil transaksi aktif (status = 'masuk') beserta tarif
  → Hitung durasi_jam = CEIL((waktu_keluar - waktu_masuk) / 60 menit)
  → Hitung biaya_total = durasi_jam × tarif_per_jam
  → Update tb_transaksi: waktu_keluar, durasi_jam, biaya_total, status = 'keluar'
  → Kurangi 1 dari kolom terisi pada area
  → Catat log aktivitas
  → Redirect ke halaman cetak struk
Selesai
```

**Pseudocode (keluar)**
```
BACA id_parkir
transaksi = QUERY("SELECT ... WHERE id_parkir=? AND status='masuk'", id_parkir)
JIKA transaksi KOSONG MAKA
    TAMPIL "Transaksi tidak ditemukan"
LAINNYA
    durasi = CEIL( (SEKARANG - transaksi.waktu_masuk) DALAM MENIT / 60 )
    durasi = MAX(1, durasi)
    biaya  = durasi * transaksi.tarif_per_jam
    UPDATE tb_transaksi SET waktu_keluar=SEKARANG, durasi_jam=durasi,
           biaya_total=biaya, status='keluar' WHERE id_parkir=?
    UPDATE tb_area_parkir SET terisi = terisi - 1 WHERE id_area = transaksi.id_area
    REDIRECT ke struk.php?id=id_parkir
```

---

## C. Proses Cetak Struk

**Deskripsi**: Setelah kendaraan keluar tercatat, sistem menampilkan rincian
transaksi (plat nomor, waktu masuk/keluar, durasi, total biaya) dalam format
struk yang siap dicetak melalui dialog cetak browser.

**Alur**
```
Mulai
  → Terima id_parkir dari URL
  → Ambil data transaksi lengkap (JOIN kendaraan, area, tarif)
  → Jika data tidak ditemukan → Tampilkan pesan error → Selesai
  → Tampilkan struk (plat, jenis, area, waktu masuk/keluar, durasi, total bayar)
  → Sediakan tombol "Cetak" (window.print())
Selesai
```

**Pseudocode**
```
BACA id_parkir DARI URL
data = QUERY("SELECT ... JOIN kendaraan, area, tarif WHERE id_parkir=?", id_parkir)
JIKA data KOSONG MAKA
    TAMPIL "Transaksi tidak ditemukan"
LAINNYA
    TAMPIL struk berisi:
        plat_nomor, jenis_kendaraan, nama_area,
        waktu_masuk, waktu_keluar, durasi_jam,
        format_rupiah(biaya_total)
    TAMPIL tombol cetak
```
