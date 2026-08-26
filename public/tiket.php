<?php
/**
 * Modul  : E-Karcis / Tiket Parkir Digital Publik (MRu-Parkir)
 * Fungsi : Menampilkan tiket digital resmi kendaraan dengan QR Code, live durasi,
 *          estimasi tarif berjalan, dan fitur cetak / bagikan struk.
 */
require_once __DIR__ . '/../includes/functions.php';

$id   = (int) ($_GET['id'] ?? 0);
$plat = strtoupper(trim(amankanInput($_GET['plat'] ?? '')));

$transaksi = null;

if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, a.nama_area, tr.tarif_per_jam, u.nama_lengkap AS petugas
         FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         JOIN tb_area_parkir a ON a.id_area = t.id_area
         JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
         LEFT JOIN tb_user u ON u.id_user = t.id_user
         WHERE t.id_parkir = ? LIMIT 1"
    );
    $stmt->execute([$id]);
    $transaksi = $stmt->fetch();
} else if ($plat !== '') {
    $stmt = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, a.nama_area, tr.tarif_per_jam, u.nama_lengkap AS petugas
         FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         JOIN tb_area_parkir a ON a.id_area = t.id_area
         JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
         LEFT JOIN tb_user u ON u.id_user = t.id_user
         WHERE k.plat_nomor = ?
         ORDER BY t.waktu_masuk DESC LIMIT 1"
    );
    $stmt->execute([$plat]);
    $transaksi = $stmt->fetch();
}

$judulHalaman = 'E-Karcis Digital';
require __DIR__ . '/../includes/header_public.php';
?>

<div class="pub-section" style="padding-top: 36px;">
  <div style="max-width: 580px; margin: 0 auto;">
    
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <a href="lacak.php" style="text-decoration:none; color:var(--ink-muted); font-size:13.5px; font-weight:600; display:inline-flex; align-items:center; gap:6px;">
        &larr; Lacak Kendaraan
      </a>
      <span class="pub-eyebrow" style="margin:0;">MRu-Parkir E-Karcis</span>
    </div>

    <?php if (!$transaksi): ?>
      <!-- State jika tiket tidak ditemukan -->
      <div class="card" style="text-align:center; padding:40px 24px;">
        <span style="font-size:42px; display:block; margin-bottom:12px;">🎟️</span>
        <h3 style="margin-bottom:8px;">Tiket Parkir Tidak Ditemukan</h3>
        <p style="color:var(--ink-muted); font-size:14px; margin-bottom:24px;">
          Nomor karcis atau plat nomor yang Anda cari tidak terdaftar dalam sistem parkir aktif saat ini.
        </p>
        <a href="lacak.php" class="btn-add" style="text-decoration:none; display:inline-flex; justify-content:center;">
          🔍 Lacak Plat Nomor Kendaraan
        </a>
      </div>

    <?php else:
      $isMasuk = $transaksi['status'] === 'masuk';
      $waktuMasukTs = strtotime($transaksi['waktu_masuk']);
      $waktuKeluarTs = $transaksi['waktu_keluar'] ? strtotime($transaksi['waktu_keluar']) : time();
      $durasiJam = hitungDurasiJam($transaksi['waktu_masuk'], $transaksi['waktu_keluar'] ?: date('Y-m-d H:i:s'));
      $biayaTotal = $transaksi['biaya_total'] > 0 ? (float)$transaksi['biaya_total'] : hitungBiaya($durasiJam, $transaksi['tarif_per_jam']);
      $nomorKarcis = 'MRU-' . date('Ymd', $waktuMasukTs) . '-' . str_pad($transaksi['id_parkir'], 5, '0', STR_PAD_LEFT);
      $urlTiket = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . strtok($_SERVER['REQUEST_URI'], '?') . "?id={$transaksi['id_parkir']}&plat=" . urlencode($transaksi['plat_nomor']);
    ?>

      <!-- E-Karcis Digital Container -->
      <div class="card e-karcis-card" style="padding:0; overflow:hidden; position:relative; border:2px solid var(--border); box-shadow:var(--shadow-lg);">
        
        <!-- Header Struk & Logo -->
        <div style="background:linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color:#fff; padding:24px 28px; text-align:center; position:relative;">
          <div style="display:flex; align-items:center; justify-content:center; gap:10px; margin-bottom:6px;">
            <img src="../assets/logo_mru.jpg" alt="Logo MRu-Parkir" style="width:32px; height:32px; border-radius:8px; object-fit:cover;">
            <strong style="font-family:var(--font-display); font-size:18px; color:#fff;">MRu-Parkir</strong>
          </div>
          <small style="color:#94A3B8; font-size:11.5px; display:block; letter-spacing:.04em;">PT MRu-Parking Teknologi Indonesia</small>
          
          <div style="margin-top:14px;">
            <span class="badge <?= $isMasuk ? 'badge-success' : 'badge-danger' ?>" style="font-size:12px; padding:6px 14px;">
              <?= $isMasuk ? 'E-TICKET PARKIR AKTIF' : 'STATUS: SUDAH KELUAR' ?>
            </span>
          </div>
        </div>

        <!-- Body Detail Karcis -->
        <div style="padding:28px;">
          
          <!-- Nombor Karcis & QR Code -->
          <div style="text-align:center; margin-bottom:24px; padding-bottom:20px; border-bottom:1.5px dashed var(--border);">
            <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Nomor Karcis Digital</span>
            <div style="font-family:var(--font-mono); font-size:20px; font-weight:800; color:var(--accent-dark); margin:4px 0 16px;">
              <?= $nomorKarcis ?>
            </div>

            <!-- QR Code Canvas / SVG Barcode Generator -->
            <div style="display:flex; justify-content:center; margin-bottom:8px;">
              <div id="qrcode" style="padding:10px; background:#fff; border:1px solid var(--border); border-radius:12px; box-shadow:var(--shadow-sm);"></div>
            </div>
            <small style="font-size:11px; color:var(--ink-muted);">Pindai QR ini pada palang pintu keluar parkir</small>
          </div>

          <!-- Grid Informasi Kendaraan -->
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px; font-size:13.5px;">
            <div>
              <span style="display:block; font-size:11px; color:var(--ink-muted); text-transform:uppercase; font-weight:600; margin-bottom:2px;">Plat Nomor Kendaraan</span>
              <strong style="font-family:var(--font-mono); font-size:18px; color:var(--ink);"><?= htmlspecialchars($transaksi['plat_nomor']) ?></strong>
            </div>

            <div>
              <span style="display:block; font-size:11px; color:var(--ink-muted); text-transform:uppercase; font-weight:600; margin-bottom:2px;">Jenis Kendaraan</span>
              <span class="badge badge-success" style="font-size:12px; margin-top:2px;">
                <?= ucfirst(htmlspecialchars($transaksi['jenis_kendaraan'])) ?>
              </span>
            </div>

            <div>
              <span style="display:block; font-size:11px; color:var(--ink-muted); text-transform:uppercase; font-weight:600; margin-bottom:2px;">Area Lokasi Parkir</span>
              <strong><?= htmlspecialchars($transaksi['nama_area']) ?></strong>
            </div>

            <div>
              <span style="display:block; font-size:11px; color:var(--ink-muted); text-transform:uppercase; font-weight:600; margin-bottom:2px;">Warna Kendaraan</span>
              <span><?= htmlspecialchars($transaksi['warna'] ?: '-') ?></span>
            </div>
          </div>

          <!-- Rincian Waktu & Biaya -->
          <div style="background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius); padding:16px; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px;">
              <span style="color:var(--ink-muted);">Waktu Masuk</span>
              <strong style="font-family:var(--font-mono);"><?= date('d M Y, H:i:s', $waktuMasukTs) ?></strong>
            </div>

            <?php if (!$isMasuk): ?>
              <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px;">
                <span style="color:var(--ink-muted);">Waktu Keluar</span>
                <strong style="font-family:var(--font-mono);"><?= date('d M Y, H:i:s', $waktuKeluarTs) ?></strong>
              </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px;">
              <span style="color:var(--ink-muted);">Durasi <?= $isMasuk ? 'Berjalan' : 'Total' ?></span>
              <strong style="font-family:var(--font-mono); color:var(--ink);"><?= $durasiJam ?> jam</strong>
            </div>

            <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:8px;">
              <span style="color:var(--ink-muted);">Tarif Per Jam</span>
              <span><?= formatRupiah($transaksi['tarif_per_jam']) ?> / jam</span>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:baseline; padding-top:10px; border-top:1px dashed var(--border); margin-top:10px;">
              <strong style="font-size:14px; color:var(--ink);"><?= $isMasuk ? 'Estimasi Total' : 'Total Biaya' ?></strong>
              <strong style="font-family:var(--font-display); font-size:22px; color:var(--accent-dark);"><?= formatRupiah($biayaTotal) ?></strong>
            </div>
          </div>

          <!-- Tombol Aksi Karcis Digital -->
          <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
            <button onclick="window.print()" class="btn-add" style="justify-content:center; background:var(--surface); color:var(--ink); border:1.5px solid var(--border); font-size:13px; font-weight:600;">
              🖨️ Cetak E-Karcis
            </button>
            <a href="https://api.whatsapp.com/send?text=<?= urlencode("E-Karcis Parkir Digital MRu-Parkir\nPlat: {$transaksi['plat_nomor']}\nKarcis: {$nomorKarcis}\nArea: {$transaksi['nama_area']}\nLihat Tiket: {$urlTiket}") ?>" target="_blank" class="btn-add" style="justify-content:center; background:#25D366; color:#fff; text-decoration:none; font-size:13px; font-weight:600;">
              📱 Kirim ke WA
            </a>
          </div>

        </div>

        <!-- Footer Struk Ketentuan -->
        <div style="background:var(--surface-alt); border-top:1px solid var(--border); padding:14px 20px; font-size:11px; color:var(--ink-muted); text-align:center; line-height:1.5;">
          • Simpan tiket digital ini sampai kendaraan Anda keluar dari area parkir.<br>
          • Kehilangan e-karcis atau barang dalam kendaraan menjadi tanggung jawab pengguna.<br>
          • Layanan Bantuan Operator: Support MRu-Parkir (021) 555-PARK.
        </div>
      </div>

    <?php endif; ?>

  </div>
</div>

<!-- QR Code Generator Library Script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  <?php if ($transaksi): ?>
    // Generate QR Code untuk nomor karcis & tiket URL
    new QRCode(document.getElementById("qrcode"), {
      text: "<?= $urlTiket ?>",
      width: 128,
      height: 128,
      colorDark : "#0F172A",
      colorLight : "#FFFFFF",
      correctLevel : QRCode.CorrectLevel.H
    });
  <?php endif; ?>
</script>

<?php require __DIR__ . '/../includes/footer_public.php'; ?>
