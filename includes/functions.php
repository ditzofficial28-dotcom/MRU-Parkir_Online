<?php
/**
 * File   : includes/functions.php
 * Fungsi : Kumpulan fungsi/prosedur yang dipakai berulang di seluruh
 *          modul aplikasi (best practice: hindari duplikasi kode)
 */
require_once __DIR__ . '/../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

/**
 * catatLog()
 * Input   : $pdo, $id_user (int), $aktivitas (string)
 * Proses  : menyisipkan satu baris ke tb_log_aktivitas
 * Output  : - (void), dipakai oleh semua modul admin utk audit trail
 */
function catatLog(PDO $pdo, int $id_user, string $aktivitas): void {
    $stmt = $pdo->prepare(
        "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES (?, ?, NOW())"
    );
    $stmt->execute([$id_user, $aktivitas]);
}

/**
 * hitungDurasiJam()
 * Input   : $waktu_masuk (string datetime), $waktu_keluar (string datetime)
 * Proses  : menghitung selisih jam, dibulatkan ke atas (setiap kelebihan
 *           menit dihitung 1 jam penuh - lazim dipakai pada tarif parkir)
 * Output  : int jumlah jam (minimal 1)
 */
function hitungDurasiJam(string $waktu_masuk, string $waktu_keluar): int {
    $masuk  = new DateTime($waktu_masuk);
    $keluar = new DateTime($waktu_keluar);
    $selisihMenit = ($keluar->getTimestamp() - $masuk->getTimestamp()) / 60;
    $jam = (int) ceil($selisihMenit / 60);
    return max(1, $jam);
}

/**
 * hitungBiaya()
 * Input   : $durasi_jam (int), $tarif_per_jam (int|float)
 * Proses  : perkalian sederhana durasi x tarif
 * Output  : float total biaya
 */
function hitungBiaya(int $durasi_jam, $tarif_per_jam): float {
    return $durasi_jam * (float) $tarif_per_jam;
}

/**
 * cekKapasitasArea()
 * Input   : $pdo, $id_area (int)
 * Proses  : membandingkan kolom 'terisi' dengan 'kapasitas' pada tb_area_parkir
 * Output  : bool true jika area masih tersedia (belum penuh)
 */
function cekKapasitasArea(PDO $pdo, int $id_area): bool {
    $stmt = $pdo->prepare("SELECT kapasitas, terisi FROM tb_area_parkir WHERE id_area = ?");
    $stmt->execute([$id_area]);
    $area = $stmt->fetch();
    if (!$area) return false;
    return (int) $area['terisi'] < (int) $area['kapasitas'];
}

/**
 * ubahTerisiArea()
 * Input   : $pdo, $id_area (int), $delta (int, +1 saat masuk / -1 saat keluar)
 * Proses  : UPDATE kolom terisi pada tb_area_parkir
 * Output  : - (void)
 */
function ubahTerisiArea(PDO $pdo, int $id_area, int $delta): void {
    $stmt = $pdo->prepare(
        "UPDATE tb_area_parkir SET terisi = GREATEST(0, terisi + ?) WHERE id_area = ?"
    );
    $stmt->execute([$delta, $id_area]);
}

/**
 * ambilTarifByJenis()
 * Input   : $pdo, $jenis_kendaraan (string: motor/mobil/lainnya)
 * Proses  : mengambil baris tarif aktif sesuai jenis kendaraan (query efisien,
 *           memakai LIMIT 1 karena hanya butuh satu baris)
 * Output  : array|false data tarif
 */
function ambilTarifByJenis(PDO $pdo, string $jenis_kendaraan) {
    $stmt = $pdo->prepare("SELECT * FROM tb_tarif WHERE jenis_kendaraan = ? LIMIT 1");
    $stmt->execute([$jenis_kendaraan]);
    return $stmt->fetch();
}

/**
 * formatRupiah()
 * Input   : $angka (int|float)
 * Output  : string format Rupiah, mis. "Rp 10.000"
 */
function formatRupiah($angka): string {
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/**
 * amankanInput()
 * Input   : $data (string)
 * Proses  : trim + htmlspecialchars, mencegah XSS pada output HTML
 * Output  : string yang sudah dibersihkan
 */
function amankanInput(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
