<?php
/**
 * Modul  : Pos Transaksi Keluar Kendaraan & Kalkulasi Pendapatan
 * Input  : plat_out (POST) atau id_parkir (GET)
 * Proses : Memproses kendaraan keluar, menghitung total durasi & biaya parkir,
 *          mengurangi slot terisi area, mencatat pendapatan, dan menerbitkan struk.
 * Output : Panel Kontrol Pos Keluar Real-Time & Tabel Transaksi Aktif
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

if (isset($_GET['lookup_detail'])) {
    header('Content-Type: application/json');
    $q = trim($_GET['lookup_detail']);
    $numId = (int) preg_replace('/[^0-9]/', '', $q);

    $stmtDetail = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, tr.tarif_per_jam, a.nama_area
         FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
         JOIN tb_area_parkir a ON a.id_area = t.id_area
         WHERE (t.id_parkir = ? OR k.plat_nomor = ? OR k.plat_nomor LIKE ?) AND t.status = 'masuk'
         ORDER BY t.waktu_masuk DESC LIMIT 1"
    );
    $stmtDetail->execute([$numId, $q, "%$q%"]);
    $row = $stmtDetail->fetch();

    if ($row) {
        $nowStr = date('Y-m-d H:i:s');
        $durasiJam = hitungDurasiJam($row['waktu_masuk'], $nowStr);
        $biayaTotal = hitungBiaya($durasiJam, (float)$row['tarif_per_jam']);

        echo json_encode([
            'success' => true,
            'status_type' => 'active',
            'data' => [
                'id_parkir'       => $row['id_parkir'],
                'plat_nomor'      => $row['plat_nomor'],
                'jenis_kendaraan' => ucfirst($row['jenis_kendaraan']),
                'warna'           => $row['warna'] ?: '-',
                'nama_area'       => $row['nama_area'],
                'waktu_masuk'     => date('d M Y, H:i', strtotime($row['waktu_masuk'])) . ' WIB',
                'waktu_keluar'    => date('d M Y, H:i', strtotime($nowStr)) . ' WIB (Saat Ini)',
                'durasi_jam'      => $durasiJam,
                'tarif_per_jam'   => formatRupiah($row['tarif_per_jam']),
                'biaya_total'     => formatRupiah($biayaTotal),
                'raw_biaya'       => $biayaTotal
            ]
        ]);
    } else {
        // 2. Cek apakah kendaraan / tiket sudah pernah KELUAR (status = 'keluar')
        $stmtKeluar = $pdo->prepare(
            "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area
             FROM tb_transaksi t
             JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
             JOIN tb_area_parkir a ON a.id_area = t.id_area
             WHERE (t.id_parkir = ? OR k.plat_nomor = ? OR k.plat_nomor LIKE ?) AND t.status = 'keluar'
             ORDER BY t.waktu_keluar DESC LIMIT 1"
        );
        $stmtKeluar->execute([$numId, $q, "%$q%"]);
        $rowKeluar = $stmtKeluar->fetch();

        if ($rowKeluar) {
            $waktuKeluarFmt = date('d M Y, H:i', strtotime($rowKeluar['waktu_keluar'])) . ' WIB';
            $biayaFmt = formatRupiah($rowKeluar['biaya_total']);
            echo json_encode([
                'success'     => false,
                'status_type' => 'already_exited',
                'plat_nomor'  => $rowKeluar['plat_nomor'],
                'waktu_keluar'=> $waktuKeluarFmt,
                'nama_area'   => $rowKeluar['nama_area'],
                'biaya_total' => $biayaFmt,
                'message'     => "⚠️ <strong>PERHATIAN: Tiket / QRIS Plat '{$rowKeluar['plat_nomor']}' SUDAH KELUAR!</strong><br>Kendaraan ini sudah diproses KELUAR dari <strong>{$rowKeluar['nama_area']}</strong> pada <strong>{$waktuKeluarFmt}</strong> (Total Biaya: {$biayaFmt}). Tiket / QRIS ini sudah tidak aktif lagi dalam sesi parkir saat ini."
            ]);
        } else {
            echo json_encode([
                'success'     => false,
                'status_type' => 'not_found',
                'message'     => "✕ Kendaraan dengan Plat / ID '$q' tidak ditemukan dalam sistem parkir."
            ]);
        }
    }
    exit;
}

if (isset($_GET['lookup_id'])) {
    header('Content-Type: application/json');
    $lid = (int) $_GET['lookup_id'];
    $st = $pdo->prepare(
        "SELECT k.plat_nomor FROM tb_transaksi t 
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan 
         WHERE t.id_parkir = ? LIMIT 1"
    );
    $st->execute([$lid]);
    $row = $st->fetch();
    echo json_encode(['plat_nomor' => $row['plat_nomor'] ?? '']);
    exit;
}

$pesan = '';
$error = '';

$aksi = $_GET['aksi'] ?? '';
$id_parkir_out = (int) ($_GET['id_parkir'] ?? 0);
$plat_out = strtoupper(trim(amankanInput($_POST['plat_out'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $plat_out !== '') {
    $numId = (int) preg_replace('/[^0-9]/', '', $plat_out);
    $stmtCekPlat = $pdo->prepare(
        "SELECT t.id_parkir FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         WHERE (t.id_parkir = ? OR k.plat_nomor = ? OR k.plat_nomor LIKE ?) AND t.status = 'masuk'
         ORDER BY t.waktu_masuk DESC LIMIT 1"
    );
    $stmtCekPlat->execute([$numId, $plat_out, "%$plat_out%"]);
    $found = $stmtCekPlat->fetch();
    if ($found) {
        $id_parkir_out = (int) $found['id_parkir'];
        $aksi = 'keluar';
    } else {
        $error = "Kendaraan / Tiket dengan kata kunci '$plat_out' tidak ditemukan dalam sesi parkir aktif saat ini.";
    }
}

if ($aksi === 'keluar' && $id_parkir_out > 0) {
    $stmtTx = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, tr.tarif_per_jam, a.id_area, a.nama_area
         FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
         JOIN tb_area_parkir a ON a.id_area = t.id_area
         WHERE t.id_parkir = ? AND t.status = 'masuk' LIMIT 1"
    );
    $stmtTx->execute([$id_parkir_out]);
    $tx = $stmtTx->fetch();

    if ($tx) {
        $waktuKeluar = date('Y-m-d H:i:s');
        $durasiJam   = hitungDurasiJam($tx['waktu_masuk'], $waktuKeluar);
        $biayaTotal  = hitungBiaya($durasiJam, (float)$tx['tarif_per_jam']);

        $stmtOut = $pdo->prepare(
            "UPDATE tb_transaksi 
             SET waktu_keluar = ?, durasi_jam = ?, biaya_total = ?, status = 'keluar' 
             WHERE id_parkir = ?"
        );
        $stmtOut->execute([$waktuKeluar, $durasiJam, $biayaTotal, $tx['id_parkir']]);

        ubahTerisiArea($pdo, (int)$tx['id_area'], -1);
        catatLog($pdo, $_SESSION['id_user'], "Proses KELUAR kendaraan {$tx['plat_nomor']} di {$tx['nama_area']} - Durasi: {$durasiJam} jam - Pendapatan: Rp " . number_format($biayaTotal, 0, ',', '.'));

        $biayaFmt = formatRupiah($biayaTotal);
        $pesan = "Kendaraan <strong>{$tx['plat_nomor']}</strong> ({$tx['nama_area']}) berhasil diproses KELUAR! Durasi: {$durasiJam} jam • Total Biaya Parkir: <strong style='font-size:16px;'>{$biayaFmt}</strong>. <a href='struk.php?id={$tx['id_parkir']}' target='_blank' style='background:#10B981; color:#ffffff; padding:5px 14px; border-radius:6px; text-decoration:none; font-weight:700; font-size:12px; margin-left:10px; display:inline-flex; align-items:center; gap:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15);'>🖨️ Cetak Struk / E-Karcis ↗</a>";
    } else {
        $error = "Transaksi parkir aktif tidak ditemukan atau kendaraan sudah diproses keluar.";
    }
}

// Sesi Parkir Aktif Siap Keluar
$sesiAktif = $pdo->query(
    "SELECT t.id_parkir, k.plat_nomor, k.jenis_kendaraan, k.warna, k.pemilik, t.waktu_masuk, a.nama_area, tr.tarif_per_jam
     FROM tb_transaksi t
     JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
     JOIN tb_area_parkir a ON a.id_area = t.id_area
     JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
     WHERE t.status = 'masuk'
     ORDER BY t.waktu_masuk DESC"
)->fetchAll();

// Transaksi Keluar Hari Ini
$todayDate = date('Y-m-d');
$stmtToday = $pdo->prepare(
    "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, a.nama_area
     FROM tb_transaksi t
     JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
     JOIN tb_area_parkir a ON a.id_area = t.id_area
     WHERE DATE(t.waktu_keluar) = ? AND t.status = 'keluar'
     ORDER BY t.waktu_keluar DESC LIMIT 20"
);
$stmtToday->execute([$todayDate]);
$transaksiHariIni = $stmtToday->fetchAll();

$judulHalaman = 'Proses Keluar (Pos Parkir)';
require __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
  <div>
    <h2 style="margin:0; font-size:22px; display:flex; align-items:center; gap:8px;"><?= icon('exit') ?> Pos Transaksi Keluar &amp; Catat Pendapatan</h2>
    <p style="margin:4px 0 0; color:var(--ink-muted); font-size:13px;">Proses kendaraan keluar pos, hitung otomatis durasi &amp; tarif parkir, serta terbitkan struk resmi.</p>
  </div>
  <a href="../public/lacak.php" target="_blank" class="btn-add" style="background:var(--accent); color:#fff; font-weight:600; text-decoration:none; padding:9px 16px; font-size:13px;">
    <?= icon('map') ?> Tampilan Publik (Lacak Kendaraan) ↗
  </a>
</div>

<?php if ($pesan): ?>
  <div style="background:var(--success-soft); color:var(--success); border:1px solid rgba(21,128,61,0.2); padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600; font-size:13.5px;">
    ✓ <?= $pesan ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:var(--danger-soft); color:var(--danger); border:1px solid rgba(185,28,28,0.2); padding:14px 18px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600; font-size:13.5px;">
    ✕ <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- Box Pos Keluar Instan & Camera/File QR Scanner -->
<div class="card" style="margin-bottom:28px; border:2px solid var(--accent); background:rgba(16, 185, 129, 0.05); padding:24px;">
  <form id="posKeluarForm" method="post" style="display:flex; flex-direction:column; gap:16px;">
    <div>
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <h3 style="margin:0; font-size:18px; color:var(--ink); display:flex; align-items:center; gap:8px;">
          <?= icon('search') ?> Scan &amp; Upload QRIS Struk / Cari Plat &amp; Proses Keluar Instan
        </h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
          <button type="button" id="toggleQrScannerBtn" class="btn-add" style="background:var(--surface-alt); color:var(--ink); border:1.5px solid var(--border); font-size:13px; padding:8px 14px; font-weight:700;">
            <?= icon('camera') ?> Kamera Scan QR
          </button>
          <button type="button" id="uploadQrImageBtn" class="btn-add" style="background:var(--accent-dark); color:#ffffff; font-size:13px; padding:8px 14px; font-weight:700;">
            <?= icon('upload') ?> Unggah Foto QRIS / Struk
          </button>
          <input type="file" id="qrFileInput" accept="image/*" style="display:none;">
        </div>
      </div>
      <p style="margin:6px 0 0; font-size:13px; color:var(--ink-muted);">Gunakan Kamera, Unggah Foto QRIS/E-Karcis, atau ketik Plat Nomor untuk langsung menghitung durasi parkir &amp; total biaya secara otomatis.</p>
    </div>

    <!-- Container Preview File Gambar / Status Detection -->
    <div id="qrFileStatusWrapper" style="display:none; background:var(--surface); border:1.5px solid var(--border); border-radius:10px; padding:12px 16px; align-items:center; gap:14px;">
      <img id="qrFilePreview" src="" alt="Preview QRIS" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid var(--border);">
      <div style="flex:1;">
        <strong id="qrFileStatusTitle" style="display:block; font-size:13.5px; color:var(--ink);">Mendeteksi Foto QR Code...</strong>
        <span id="qrFileStatusText" style="font-size:12px; color:var(--ink-muted);">Menganalisis data QRIS dari file gambar...</span>
      </div>
    </div>

    <!-- Live Camera Viewport Container (Hidden by default) -->
    <div id="qrScannerWrapper" style="display:none; background:var(--surface); border:2px dashed var(--accent); border-radius:12px; padding:16px; text-align:center;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <strong style="color:var(--accent-dark); font-size:14px; display:flex; align-items:center; gap:6px;"><?= icon('camera') ?> Live Camera QR Reader — Arahkan QR Struk ke Kamera</strong>
        <button type="button" id="closeQrScannerBtn" style="background:#EF4444; color:#fff; border:none; padding:4px 10px; border-radius:6px; font-weight:700; cursor:pointer; font-size:12px;">✕ Tutup Kamera</button>
      </div>
      <div id="qr-reader" style="width:100%; max-width:400px; margin:0 auto; border-radius:8px; overflow:hidden;"></div>
      <div id="qr-reader-results" style="margin-top:10px; font-weight:700; color:var(--accent-dark); font-size:13px;"></div>
    </div>

    <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
      <input type="text" name="plat_out" id="plat_out" placeholder="Ketik / Scan Plat Nomor / ID Struk (mis. B 1234 ABC)..." required autofocus style="width:100%; padding:12px 18px; font-size:16px; text-transform:uppercase; font-weight:700; font-family:var(--font-mono); border:2px solid var(--accent); border-radius:10px; transition:all 0.3s ease;">
    </div>
  </form>
</div>

<!-- Card Rincian Kendaraan Hasil Scan / Upload QRIS (Tampil di bawah Card Ketik/Scan) -->
<div id="qrScanDetailCard" class="card" style="display:none; margin-bottom:28px; border:2px solid var(--accent); background:var(--surface); padding:24px; box-shadow:var(--shadow-lg); border-radius:var(--radius-lg);">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px; padding-bottom:14px; border-bottom:1.5px dashed var(--border);">
    <div>
      <span class="pub-eyebrow" style="margin-bottom:2px;">Deteksi QRIS Berhasil</span>
      <h3 style="margin:0; font-size:20px; font-family:var(--font-display); display:flex; align-items:center; gap:8px;">
        <?= icon('car') ?> Rincian Kendaraan Siap Keluar
      </h3>
    </div>
    <span class="badge badge-warning" style="font-size:13px; padding:6px 14px; font-weight:700;">
      🟢 Sesi Parkir Aktif
    </span>
  </div>

  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:22px;">
    <div style="background:var(--surface-alt); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid var(--border);">
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); display:block; margin-bottom:3px;">Plat Nomor Kendaraan</span>
      <strong id="detPlat" style="font-family:var(--font-mono); font-size:19px; color:var(--accent-dark); display:block;">-</strong>
    </div>

    <div style="background:var(--surface-alt); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid var(--border);">
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); display:block; margin-bottom:3px;">Jenis &amp; Warna Kendaraan</span>
      <div style="display:flex; align-items:center; gap:8px; margin-top:2px;">
        <span id="detJenis" class="badge badge-success" style="font-size:12px;">-</span>
        <strong id="detWarna" style="font-size:14px; color:var(--ink);">-</strong>
      </div>
    </div>

    <div style="background:var(--surface-alt); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid var(--border);">
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); display:block; margin-bottom:3px;">Lokasi Area Parkir</span>
      <strong id="detArea" style="font-size:14.5px; color:var(--accent-dark); display:block;">-</strong>
    </div>

    <div style="background:var(--surface-alt); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid var(--border);">
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); display:block; margin-bottom:3px;">Waktu Jam Masuk</span>
      <strong id="detWaktuMasuk" style="font-family:var(--font-mono); font-size:13.5px; color:var(--ink); display:block;">-</strong>
    </div>

    <div style="background:var(--surface-alt); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid var(--border);">
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); display:block; margin-bottom:3px;">Estimasi Waktu Keluar</span>
      <strong id="detWaktuKeluar" style="font-family:var(--font-mono); font-size:13.5px; color:var(--ink); display:block;">-</strong>
    </div>

    <div style="background:var(--surface-alt); padding:12px 16px; border-radius:var(--radius-sm); border:1px solid var(--border);">
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.05em; color:var(--ink-muted); display:block; margin-bottom:3px;">Durasi &amp; Tarif Parkir</span>
      <strong id="detDurasiTarif" style="font-size:14px; color:var(--ink); display:block;">-</strong>
    </div>
  </div>

  <!-- Banner Tagihan Biaya Parkir -->
  <div style="background:linear-gradient(135deg, #0F172A 0%, #1E293B 100%); color:#ffffff; padding:18px 24px; border-radius:var(--radius); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <span style="font-size:11.5px; text-transform:uppercase; letter-spacing:.08em; color:#94A3B8; display:block; margin-bottom:2px;">TOTAL TAGIHAN BIAYA PARKIR</span>
      <strong id="detTotalBiaya" style="font-family:var(--font-display); font-size:28px; color:#34D399;">Rp 0</strong>
    </div>
    <button type="button" id="detSubmitBtn" class="btn-add" style="padding:12px 26px; font-size:15px; background:var(--accent); font-weight:800; border-radius:10px; cursor:pointer;">
      <?= icon('exit') ?> Konfirmasi &amp; Proses Keluar Instant Sekarang →
    </button>
  </div>
</div>

<!-- Card Notifikasi Peringatan Tiket / QRIS Sudah Keluar (Tidak Aktif) -->
<div id="qrScanExitedCard" class="card" style="display:none; margin-bottom:28px; border:2px solid var(--danger); background:var(--danger-soft); padding:24px; box-shadow:var(--shadow-lg); border-radius:var(--radius-lg);">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:14px; padding-bottom:12px; border-bottom:1.5px dashed rgba(185, 28, 28, 0.3);">
    <div>
      <span class="pub-eyebrow" style="color:var(--danger); margin-bottom:2px;">⚠️ Peringatan Transaksi Parkir</span>
      <h3 style="margin:0; font-size:20px; font-family:var(--font-display); color:var(--danger); display:flex; align-items:center; gap:8px;">
        Tiket / QRIS Sudah Keluar &amp; Tidak Aktif
      </h3>
    </div>
    <span class="badge badge-danger" style="font-size:13px; padding:6px 14px; font-weight:700; background:var(--danger); color:#ffffff;">
      🔴 STATUS: SUDAH KELUAR PARKIR
    </span>
  </div>

  <div style="background:var(--surface); padding:16px 20px; border-radius:var(--radius-sm); border:1px solid rgba(185, 28, 28, 0.3); margin-bottom:16px;">
    <p id="exitedCardMsg" style="margin:0; font-size:14.5px; color:var(--ink); line-height:1.6; font-weight:600;">
      Kendaraan dengan plat ini sudah diproses keluar dari area parkir.
    </p>
  </div>

  <div style="display:flex; justify-content:flex-end;">
    <button type="button" onclick="document.getElementById('qrScanExitedCard').style.display='none';" class="btn-add" style="background:var(--surface-alt); color:var(--ink); border:1px solid var(--border); padding:8px 18px; font-size:13px; font-weight:700; cursor:pointer;">
      ✕ Tutup Peringatan
    </button>
  </div>
</div>

<!-- Tabel Kendaraan Sedang Terparkir (Siap Keluar) -->
<div class="card" style="margin-bottom:28px;">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px; display:flex; align-items:center; gap:8px;">
    <?= icon('car') ?> Daftar Kendaraan Sedang Terparkir Saat Ini (<?= count($sesiAktif) ?> Kendaraan)
  </h3>
  
  <div style="overflow-x:auto;">
    <table style="width:100%;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Plat Nomor</th>
          <th>Jenis Kendaraan</th>
          <th>Area Lokasi</th>
          <th>Waktu Masuk</th>
          <th>Durasi Saat Ini</th>
          <th>Estimasi Biaya</th>
          <th>Aksi Pos Keluar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sesiAktif as $s):
          $durasi = hitungDurasiJam($s['waktu_masuk'], date('Y-m-d H:i:s'));
          $estimasi = hitungBiaya($durasi, $s['tarif_per_jam']);
        ?>
        <tr>
          <td>#<?= $s['id_parkir'] ?></td>
          <td><strong style="font-family:var(--font-mono); font-size:15px; color:var(--ink);"><?= htmlspecialchars($s['plat_nomor']) ?></strong></td>
          <td><span class="badge badge-success"><?= htmlspecialchars(ucfirst($s['jenis_kendaraan'])) ?></span></td>
          <td><strong><?= htmlspecialchars($s['nama_area']) ?></strong></td>
          <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($s['waktu_masuk']))) ?></td>
          <td><strong><?= $durasi ?> jam</strong></td>
          <td><strong style="color:var(--accent-dark); font-size:15px;"><?= formatRupiah($estimasi) ?></strong></td>
          <td style="white-space:nowrap;">
            <a href="keluar.php?aksi=keluar&id_parkir=<?= $s['id_parkir'] ?>" onclick="return confirm('Proses KELUAR untuk kendaraan <?= htmlspecialchars($s['plat_nomor']) ?>? Total biaya estimasi: <?= formatRupiah($estimasi) ?>')" class="btn-add" style="padding:6px 14px; font-size:12.5px; background:#D97706; text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
              🚪 Proses Keluar
            </a>
            <a href="struk.php?id=<?= $s['id_parkir'] ?>" target="_blank" class="btn btn-edit" style="font-size:12px; padding:6px 10px; text-decoration:none;">
              🖨️ Struk
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$sesiAktif): ?>
          <tr><td colspan="8" style="text-align:center; color:var(--ink-muted); padding:28px;">Tidak ada kendaraan yang sedang terparkir saat ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Tabel Transaksi Keluar Hari Ini -->
<div class="card">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px; display:flex; align-items:center; gap:8px;">
    📋 Riwayat Kendaraan Keluar Hari Ini (<?= count($transaksiHariIni) ?> Transaksi)
  </h3>
  
  <div style="overflow-x:auto;">
    <table style="width:100%;">
      <thead>
        <tr>
          <th>No. Transaksi</th>
          <th>Plat Nomor</th>
          <th>Jenis</th>
          <th>Area Parkir</th>
          <th>Waktu Masuk</th>
          <th>Waktu Keluar</th>
          <th>Durasi</th>
          <th>Total Biaya</th>
          <th>Struk</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transaksiHariIni as $t): ?>
        <tr>
          <td>#MRU-<?= str_pad($t['id_parkir'], 5, '0', STR_PAD_LEFT) ?></td>
          <td><strong style="font-family:var(--font-mono); font-size:14px;"><?= htmlspecialchars($t['plat_nomor']) ?></strong></td>
          <td><span class="badge badge-success"><?= htmlspecialchars(ucfirst($t['jenis_kendaraan'])) ?></span></td>
          <td><?= htmlspecialchars($t['nama_area']) ?></td>
          <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['waktu_masuk']))) ?></td>
          <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($t['waktu_keluar']))) ?></td>
          <td><?= $t['durasi_jam'] ?> jam</td>
          <td><strong style="color:var(--accent-dark);"><?= formatRupiah($t['biaya_total']) ?></strong></td>
          <td>
            <a href="struk.php?id=<?= $t['id_parkir'] ?>" target="_blank" class="btn btn-edit" style="font-size:12px; padding:4px 8px; text-decoration:none;">
              🖨️ Cetak
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$transaksiHariIni): ?>
          <tr><td colspan="9" style="text-align:center; color:var(--ink-muted); padding:24px;">Belum ada kendaraan yang diproses keluar hari ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- HTML5 QR Code Reader Library -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var html5QrCode = null;
    var toggleBtn = document.getElementById("toggleQrScannerBtn");
    var closeBtn = document.getElementById("closeQrScannerBtn");
    var wrapper = document.getElementById("qrScannerWrapper");
    var platInput = document.getElementById("plat_out");
    var resultDiv = document.getElementById("qr-reader-results");
    var isScanning = false;

    // Map sesi aktif untuk resolusi cepat ID -> Plat Nomor
    var activeParkirMap = {
      <?php foreach ($sesiAktif as $s): ?>
        "<?= $s['id_parkir'] ?>": <?= json_encode($s['plat_nomor']) ?>,
      <?php endforeach; ?>
    };

    function extractPlatPattern(str) {
        if (!str) return "";
        var matchPlat = str.match(/[A-Z]{1,2}\s*\d{1,4}\s*[A-Z]{1,3}/i);
        if (matchPlat) {
            return matchPlat[0].toUpperCase();
        }
        return str.toUpperCase();
    }

    function resolvePlatFromQr(text, callback) {
        if (!text) { callback(""); return; }
        text = text.trim();

        // 1. Ekstrak dari parameter URL ?plat=... jika ada
        var matchPlatParam = text.match(/[?&]plat=([^&]+)/i);
        if (matchPlatParam) {
            callback(decodeURIComponent(matchPlatParam[1]).replace(/\+/g, ' ').toUpperCase());
            return;
        }

        // 2. Coba parse jika format JSON
        try {
            var jsonObj = JSON.parse(text);
            if (jsonObj && jsonObj.plat) {
                callback(jsonObj.plat.toUpperCase());
                return;
            }
        } catch(e) {}

        // 3. Ekstrak ID dari URL (?id=12) atau format Karcis (MRU-20260812-00012 / MRU-00012 / #MRU-00012)
        var targetId = null;
        var matchIdParam = text.match(/[?&]id=(\d+)/i);
        if (matchIdParam) {
            targetId = parseInt(matchIdParam[1], 10);
        } else {
            var matchNomorKarcis = text.match(/MRU-\d+-(\d+)/i);
            if (matchNomorKarcis) {
                targetId = parseInt(matchNomorKarcis[1], 10);
            } else {
                var matchMru = text.match(/MRU-?(\d+)/i);
                if (matchMru) {
                    targetId = parseInt(matchMru[1], 10);
                }
            }
        }

        if (targetId) {
            // Cek di map lokal sesi aktif lebih dulu
            if (activeParkirMap[targetId]) {
                callback(activeParkirMap[targetId].toUpperCase());
                return;
            }
            // Lakukan AJAX lookup ke server jika tidak ada di lokal map
            fetch('keluar.php?lookup_id=' + targetId)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.plat_nomor) {
                        callback(data.plat_nomor.toUpperCase());
                    } else {
                        callback(extractPlatPattern(text));
                    }
                })
                .catch(function() {
                    callback(extractPlatPattern(text));
                });
            return;
        }

        // 4. Fallback jika berupa string Plat Nomor langsung
        callback(extractPlatPattern(text));
    }

    function fetchAndRenderDetailCard(platOrId) {
        if (!platOrId) return;
        var detailCard = document.getElementById('qrScanDetailCard');
        var exitedCard = document.getElementById('qrScanExitedCard');

        fetch('keluar.php?lookup_detail=' + encodeURIComponent(platOrId))
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (res.success && res.data) {
                    if (exitedCard) exitedCard.style.display = 'none';

                    var d = res.data;
                    document.getElementById('detPlat').textContent = d.plat_nomor;
                    document.getElementById('detJenis').textContent = d.jenis_kendaraan;
                    document.getElementById('detWarna').textContent = d.warna;
                    document.getElementById('detArea').textContent = d.nama_area;
                    document.getElementById('detWaktuMasuk').textContent = d.waktu_masuk;
                    document.getElementById('detWaktuKeluar').textContent = d.waktu_keluar;
                    document.getElementById('detDurasiTarif').textContent = d.durasi_jam + " jam (" + d.tarif_per_jam + "/jam)";
                    document.getElementById('detTotalBiaya').textContent = d.biaya_total;

                    if (detailCard) {
                        detailCard.style.display = 'block';
                        detailCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                } else if (res.status_type === 'already_exited') {
                    if (detailCard) detailCard.style.display = 'none';

                    if (exitedCard) {
                        document.getElementById('exitedCardMsg').innerHTML = res.message;
                        exitedCard.style.display = 'block';
                        exitedCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }

                    platInput.style.borderColor = "#EF4444";
                    platInput.style.boxShadow = "0 0 0 4px rgba(239, 68, 68, 0.25)";

                    var fileStatusTitle = document.getElementById("qrFileStatusTitle");
                    var fileStatusText = document.getElementById("qrFileStatusText");
                    if (fileStatusTitle) {
                        fileStatusTitle.style.color = "#EF4444";
                        fileStatusTitle.innerHTML = "⚠️ PERHATIAN: Tiket / QRIS Sudah Keluar!";
                        fileStatusText.innerHTML = "Kendaraan " + (res.plat_nomor || platOrId) + " sudah diproses keluar pada " + res.waktu_keluar + " (" + res.nama_area + ").";
                    }

                    var resultDiv = document.getElementById("qr-reader-results");
                    if (resultDiv) {
                        resultDiv.style.color = "#EF4444";
                        resultDiv.innerHTML = "⚠️ PERHATIAN: Plat " + (res.plat_nomor || platOrId) + " sudah diproses keluar pada " + res.waktu_keluar + "!";
                    }
                } else {
                    if (detailCard) detailCard.style.display = 'none';
                    if (exitedCard) exitedCard.style.display = 'none';

                    platInput.style.borderColor = "#EF4444";
                    platInput.style.boxShadow = "0 0 0 4px rgba(239, 68, 68, 0.25)";

                    var fileStatusTitle = document.getElementById("qrFileStatusTitle");
                    var fileStatusText = document.getElementById("qrFileStatusText");
                    if (fileStatusTitle) {
                        fileStatusTitle.style.color = "#EF4444";
                        fileStatusTitle.innerHTML = "✕ Data Tidak Ditemukan";
                        fileStatusText.innerHTML = res.message || "Data kendaraan tidak ditemukan dalam sesi parkir.";
                    }
                }
            })
            .catch(function(err) {});
    }

    var detSubmitBtn = document.getElementById('detSubmitBtn');
    if (detSubmitBtn) {
        detSubmitBtn.addEventListener('click', function() {
            document.getElementById('posKeluarForm').submit();
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        resolvePlatFromQr(decodedText, function(platNomor) {
            platInput.value = platNomor;
            resultDiv.innerHTML = "✓ Terdeteksi Plat Nomor: <strong style='font-size:15px; color:var(--accent-dark);'>" + platNomor + "</strong>. Silakan periksa rincian data &amp; tagihan di bawah.";
            
            platInput.style.borderColor = "#10B981";
            platInput.style.boxShadow = "0 0 0 4px rgba(16, 185, 129, 0.25)";
            platInput.focus();

            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(function() {
                    wrapper.style.display = "none";
                    isScanning = false;
                }).catch(function(err) {});
            }

            fetchAndRenderDetailCard(platNomor);
        });
    }

    toggleBtn.addEventListener("click", function() {
        if (isScanning) {
            html5QrCode.stop().then(function() {
                wrapper.style.display = "none";
                isScanning = false;
            });
        } else {
            wrapper.style.display = "block";
            html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                onScanSuccess
            ).then(function() {
                isScanning = true;
            }).catch(function(err) {
                alert("Kamera tidak dapat diakses: " + err);
            });
        }
    });

    if (closeBtn) {
        closeBtn.addEventListener("click", function() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(function() {
                    wrapper.style.display = "none";
                    isScanning = false;
                });
            } else {
                wrapper.style.display = "none";
            }
        });
    }

    // --- HANDLE UPLOAD FOTO QRIS / QR STRUK ---
    var uploadBtn = document.getElementById("uploadQrImageBtn");
    var fileInput = document.getElementById("qrFileInput");
    var fileStatusWrapper = document.getElementById("qrFileStatusWrapper");
    var filePreview = document.getElementById("qrFilePreview");
    var fileStatusTitle = document.getElementById("qrFileStatusTitle");
    var fileStatusText = document.getElementById("qrFileStatusText");

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener("click", function() {
            fileInput.click();
        });

        fileInput.addEventListener("change", function(e) {
            if (e.target.files.length === 0) return;
            var file = e.target.files[0];
            
            // Preview image thumbnail
            var reader = new FileReader();
            reader.onload = function(evt) {
                filePreview.src = evt.target.result;
                fileStatusWrapper.style.display = "flex";
                fileStatusTitle.style.color = "var(--ink)";
                fileStatusTitle.innerHTML = "🔍 Membaca Foto QRIS / Struk...";
                fileStatusText.innerHTML = "Menganalisis data dari gambar " + file.name + "...";
            };
            reader.readAsDataURL(file);

            // Scan file dengan Html5Qrcode.scanFile
            var scannerInstance = html5QrCode || new Html5Qrcode("qr-reader");
            scannerInstance.scanFile(file, true)
                .then(function(decodedText) {
                    resolvePlatFromQr(decodedText, function(platNomor) {
                        platInput.value = platNomor;

                        fileStatusTitle.style.color = "#10B981";
                        fileStatusTitle.innerHTML = "✓ Terdeteksi Plat Nomor: <strong>" + platNomor + "</strong>";
                        fileStatusText.innerHTML = "Data Rincian Kendaraan &amp; Tagihan telah ditampilkan di bawah.";

                        platInput.style.borderColor = "#10B981";
                        platInput.style.boxShadow = "0 0 0 4px rgba(16, 185, 129, 0.25)";
                        platInput.focus();

                        fetchAndRenderDetailCard(platNomor);
                    });
                })
                .catch(function(err) {
                    fileStatusTitle.style.color = "#EF4444";
                    fileStatusTitle.innerHTML = "✕ QR Code Tidak Terdeteksi pada Foto";
                    fileStatusText.innerHTML = "Pastikan foto memuat QR Code / E-Karcis yang jernih dan tajam.";
                });
        });
    }

    platInput.addEventListener("change", function() {
        if (platInput.value.trim() !== '') {
            fetchAndRenderDetailCard(platInput.value.trim());
        }
    });

    var posForm = document.getElementById('posKeluarForm');
    if (posForm) {
        posForm.addEventListener('submit', function(e) {
            var card = document.getElementById('qrScanDetailCard');
            if (card && card.style.display === 'none' && platInput.value.trim() !== '') {
                e.preventDefault();
                fetchAndRenderDetailCard(platInput.value.trim());
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
