<?php
/**
 * Modul  : Cetak Struk Parkir (Admin Panel)
 * Input  : id (id_parkir) via GET
 * Proses : Ambil data transaksi lengkap (JOIN kendaraan, tarif, area)
 * Output : Tampilan struk siap cetak (window.print via CSS media print)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
cekLogin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area, tr.tarif_per_jam
     FROM tb_transaksi t
     JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
     JOIN tb_area_parkir a ON a.id_area = t.id_area
     JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
     WHERE t.id_parkir = ?"
);
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    die('Transaksi tidak ditemukan.');
}

$judulHalaman = 'Cetak Struk';
require __DIR__ . '/../includes/header.php';
?>
<a href="kendaraan.php" style="display:inline-block; margin-bottom:16px; font-weight:600; text-decoration:none; color:var(--accent-dark);">&larr; Kembali ke Manajemen Kendaraan</a>

<div class="struk" style="max-width:380px; margin:0 auto; background:var(--surface); padding:24px; border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow);">
  <p style="text-align:center; margin-top:0;">
    <strong style="font-size:16px;">STRUK PARKIR MRu-Parkir</strong><br>
    <small style="color:var(--ink-muted);">PT MRu-Parking Teknologi Indonesia</small><br>
    =======================================
  </p>
  <p style="font-family:var(--font-mono); font-size:13px; line-height:1.6;">
     No. Transaksi : #MRU-<?= str_pad($data['id_parkir'], 5, '0', STR_PAD_LEFT) ?><br>
     Plat Nomor    : <strong><?= htmlspecialchars($data['plat_nomor']) ?></strong><br>
     Jenis         : <?= htmlspecialchars(ucfirst($data['jenis_kendaraan'])) ?><br>
     Area          : <?= htmlspecialchars($data['nama_area']) ?><br>
     Waktu Masuk   : <?= htmlspecialchars(date('d M Y, H:i', strtotime($data['waktu_masuk']))) ?><br>
     Waktu Keluar  : <?= $data['waktu_keluar'] ? htmlspecialchars(date('d M Y, H:i', strtotime($data['waktu_keluar']))) : '-' ?><br>
     Durasi        : <?= $data['durasi_jam'] ?: '-' ?> jam<br>
     Tarif/Jam     : <?= formatRupiah($data['tarif_per_jam']) ?><br>
     ---------------------------------------<br>
     TOTAL BAYAR   : <strong style="font-size:16px; color:var(--accent-dark);"><?= formatRupiah($data['biaya_total']) ?></strong>
  </p>
  <p style="text-align:center; color:var(--ink-muted); font-size:12px; margin-bottom:0;">
    Terima Kasih Atas Kunjungan Anda<br>
    =======================================
  </p>
</div>

<p style="text-align:center; margin-top:20px;">
  <button onclick="window.print()" class="btn-add" style="padding:10px 24px; font-size:14px;">
    <?= icon('print') ?> Cetak Struk Parkir
  </button>
</p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
