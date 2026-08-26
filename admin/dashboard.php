<?php
/**
 * Modul  : Master Command Center (Dashboard Admin Utama MRu-Parkir)
 * Proses : - Auto-sync data terisi tb_area_parkir secara real-time
 *          - Kalkulasi KPI: Sesi Aktif, Sisa Slot, Pendapatan Hari Ini, Transaksi Hari Ini
 *          - Monitoring Okupansi Area, Quick Control Hub, Full-Width Audit Log & Spot List Real-Time
 * Output : Panel Kontrol Enterprise Admin yang terhubung penuh ke publik & database
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

$pesan = '';
$error = '';

// --- PROSES TRANSAKSI KELUAR & CALCULATE REVENUE ---
$aksi = $_GET['aksi'] ?? '';
$id_parkir_out = (int) ($_GET['id_parkir'] ?? 0);
$plat_out = strtoupper(trim(amankanInput($_POST['plat_out'] ?? '')));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $plat_out !== '') {
    $stmtCekPlat = $pdo->prepare(
        "SELECT t.id_parkir FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         WHERE (k.plat_nomor = ? OR k.plat_nomor LIKE ?) AND t.status = 'masuk'
         ORDER BY t.waktu_masuk DESC LIMIT 1"
    );
    $stmtCekPlat->execute([$plat_out, "%$plat_out%"]);
    $found = $stmtCekPlat->fetch();
    if ($found) {
        $id_parkir_out = (int) $found['id_parkir'];
        $aksi = 'keluar';
    } else {
        $error = "Kendaraan dengan plat '$plat_out' tidak ditemukan dalam sesi parkir aktif saat ini.";
    }
}

if ($aksi === 'keluar' && $id_parkir_out > 0) {
    $stmtTx = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, tr.tarif_per_jam, a.id_area, a.nama_area
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
        $pesan = "Kendaraan <strong>{$tx['plat_nomor']}</strong> berhasil diproses KELUAR! Durasi: {$durasiJam} jam • Total Biaya (Pendapatan): <strong style='font-size:15px;'>{$biayaFmt}</strong>. <a href='struk.php?id={$tx['id_parkir']}' target='_blank' style='background:#10B981; color:#ffffff; padding:5px 14px; border-radius:6px; text-decoration:none; font-weight:700; font-size:12px; margin-left:10px; display:inline-flex; align-items:center; gap:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15);'>🖨️ Cetak Struk / E-Karcis ↗</a>";
    } else {
        $error = "Transaksi parkir aktif tidak ditemukan atau kendaraan sudah keluar.";
    }
}

// Auto-sync jumlah terisi tb_area_parkir dengan transaksi aktif status 'masuk'
$pdo->exec("UPDATE tb_area_parkir a SET terisi = (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')");

// --- Query Database & KPI Stats ---
$areaList = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area")->fetchAll();

$rekapArea = $pdo->query("SELECT COALESCE(SUM(kapasitas),0) k, COALESCE(SUM(terisi),0) t FROM tb_area_parkir")->fetch();
$totalKapasitas = (int) $rekapArea['k'];
$totalTerisi    = (int) $rekapArea['t'];
$sisaTotal      = max(0, $totalKapasitas - $totalTerisi);
$ketersediaanPct = $totalKapasitas > 0 ? round((($totalKapasitas - $totalTerisi) / $totalKapasitas) * 100, 1) : 0;

// Revenue & Transaksi Hari Ini
$todayDate = date('Y-m-d');
$stmtTodayRev = $pdo->prepare("SELECT COALESCE(SUM(biaya_total),0) rev, COUNT(*) cnt FROM tb_transaksi WHERE DATE(waktu_keluar) = ? AND status = 'keluar'");
$stmtTodayRev->execute([$todayDate]);
$todayStat = $stmtTodayRev->fetch();
$pendapatanHariIni = (float) $todayStat['rev'];
$transaksiKeluarHariIni = (int) $todayStat['cnt'];

$totalMasterKendaraan = (int) $pdo->query("SELECT COUNT(*) FROM tb_kendaraan")->fetchColumn();
$rataTarif = (float) $pdo->query("SELECT COALESCE(AVG(tarif_per_jam),0) a FROM tb_tarif")->fetch()['a'];

// Sesi Parkir Aktif (Limit 15)
$sesiAktif = $pdo->query(
    "SELECT t.id_parkir, k.plat_nomor, k.jenis_kendaraan, k.warna, t.waktu_masuk, a.nama_area, tr.tarif_per_jam
     FROM tb_transaksi t
     JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
     JOIN tb_area_parkir a ON a.id_area = t.id_area
     JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
     WHERE t.status = 'masuk'
     ORDER BY t.waktu_masuk DESC LIMIT 15"
)->fetchAll();

// Log Aktivitas Terbaru (Limit 8)
$logTerbaru = $pdo->query(
    "SELECT l.*, u.nama_lengkap 
     FROM tb_log_aktivitas l
     JOIN tb_user u ON u.id_user = l.id_user
     ORDER BY l.waktu_aktivitas DESC LIMIT 8"
)->fetchAll();

$judulHalaman = 'Master Command Center';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header Action Banner -->
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px; background:var(--surface); border:1px solid var(--border); padding:20px 24px; border-radius:var(--radius-lg); box-shadow:var(--shadow-sm);">
  <div>
    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
      <span class="badge badge-success" style="font-size:11.5px; padding:4px 10px;">🟢 Live Sync Connected</span>
      <span style="font-size:12px; color:var(--ink-muted);"><?= date('l, d F Y') ?></span>
    </div>
    <h2 style="margin:0; font-size:22px;">Command Center Dashboard Admin</h2>
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="../public/lacak.php" target="_blank" class="btn-add" style="text-decoration:none; background:var(--surface-alt); color:var(--ink); border:1px solid var(--border); font-size:13px; font-weight:600;">
      <?= icon('map') ?> Lacak Kendaraan Publik ↗
    </a>
    <a href="kendaraan.php" class="btn-add" style="text-decoration:none; background:var(--accent); color:#fff; font-size:13px; font-weight:600;">
      <?= icon('car') ?> + Kelola Kendaraan
    </a>
  </div>
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

<!-- 6 Enterprise KPI Stat Cards Grid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:28px;">
  <div class="card" style="padding:18px; position:relative; overflow:hidden; border-top:4px solid var(--accent); margin-bottom:0;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Sedang Parkir (Aktif)</span>
    <strong style="display:block; font-family:var(--font-display); font-size:28px; color:var(--accent-dark); margin-top:4px;"><?= $totalTerisi ?></strong>
    <span style="font-size:11.5px; color:var(--ink-muted);">Terbaca di lacak publik</span>
  </div>

  <div class="card" style="padding:18px; position:relative; overflow:hidden; border-top:4px solid var(--brand-blue); margin-bottom:0;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Sisa Kuota Slot</span>
    <strong style="display:block; font-family:var(--font-display); font-size:28px; color:var(--brand-blue); margin-top:4px;"><?= $sisaTotal ?> Slot</strong>
    <span style="font-size:11.5px; color:var(--ink-muted);">Kapasitas: <?= $totalKapasitas ?></span>
  </div>

  <div class="card" style="padding:18px; position:relative; overflow:hidden; border-top:4px solid var(--success); margin-bottom:0;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Pendapatan Hari Ini</span>
    <strong style="display:block; font-family:var(--font-display); font-size:24px; color:var(--success); margin-top:6px;"><?= formatRupiah($pendapatanHariIni) ?></strong>
    <span style="font-size:11.5px; color:var(--ink-muted);"><?= $transaksiKeluarHariIni ?> kendaraan keluar</span>
  </div>

  <div class="card" style="padding:18px; position:relative; overflow:hidden; border-top:4px solid var(--brand-orange); margin-bottom:0;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Status Ketersediaan</span>
    <strong style="display:block; font-family:var(--font-display); font-size:28px; color:var(--brand-orange); margin-top:4px;"><?= $ketersediaanPct ?>%</strong>
    <span style="font-size:11.5px; color:var(--ink-muted);">Slot kosong tersedia</span>
  </div>

  <div class="card" style="padding:18px; position:relative; overflow:hidden; border-top:4px solid var(--brand-purple); margin-bottom:0;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Master Kendaraan</span>
    <strong style="display:block; font-family:var(--font-display); font-size:28px; color:var(--ink); margin-top:4px;"><?= $totalMasterKendaraan ?></strong>
    <span style="font-size:11.5px; color:var(--ink-muted);"><a href="kendaraan.php" style="color:var(--accent-dark); font-weight:600; text-decoration:none;">Buka CRUD →</a></span>
  </div>

  <div class="card" style="padding:18px; position:relative; overflow:hidden; border-top:4px solid #64748B; margin-bottom:0;">
    <span style="font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:var(--ink-muted); font-weight:700;">Rata-rata Tarif</span>
    <strong style="display:block; font-family:var(--font-display); font-size:22px; color:var(--ink); margin-top:6px;"><?= formatRupiah($rataTarif) ?></strong>
    <span style="font-size:11.5px; color:var(--ink-muted);">Per jam operasional</span>
  </div>
</div>

<!-- Main Split Layout: Left Area Monitoring | Right Control Hub -->
<div style="display:grid; grid-template-columns: 1.2fr 1fr; gap:20px; margin-bottom:32px; align-items:start;">
  <!-- Left Side: Area Monitoring -->
  <div>
    <h3 style="margin-top:0; font-size:17px; margin-bottom:14px;">Monitoring Okupansi Area Parkir</h3>
    <div class="floor-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px;">
      <?php foreach ($areaList as $a): $pct = $a['kapasitas'] > 0 ? min(100, round($a['terisi'] / $a['kapasitas'] * 100)) : 0; ?>
        <div class="floor-card" style="padding:16px;">
          <div class="floor-info">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div class="floor-name" style="font-size:14px;"><?= htmlspecialchars($a['nama_area']) ?></div>
              <span class="badge <?= $pct >= 90 ? 'badge-danger' : ($pct >= 60 ? 'badge-warning' : 'badge-success') ?>"><?= $pct ?>%</span>
            </div>
            <div class="occupancy-bar" style="margin-top:8px; height:8px;">
              <div class="occupancy-fill <?= $pct >= 90 ? 'fill-danger' : ($pct >= 60 ? 'fill-warning' : 'fill-success') ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="floor-stats" style="margin-top:8px; font-size:12px;">
              <div><span>Terisi</span><strong><?= $a['terisi'] ?></strong></div>
              <div><span>Sisa</span><strong><?= $a['kapasitas'] - $a['terisi'] ?></strong></div>
              <div><span>Kapasitas</span><strong><?= $a['kapasitas'] ?></strong></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Right Side: Control Hub Navigasi -->
  <div>
    <h3 style="margin-top:0; font-size:17px; margin-bottom:14px;">Control Hub Navigasi Admin</h3>
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px;">
      <a href="kendaraan.php" style="text-decoration:none; padding:14px 10px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--ink); font-weight:600; display:block;">
        <span class="stat-icon stat-icon-green" style="margin:0 auto 8px; width:36px; height:36px;"><?= icon('car') ?></span>
        <span style="font-size:12px; display:block;">CRUD Kendaraan</span>
      </a>
      <a href="area.php" style="text-decoration:none; padding:14px 10px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--ink); font-weight:600; display:block;">
        <span class="stat-icon stat-icon-blue" style="margin:0 auto 8px; width:36px; height:36px;"><?= icon('map') ?></span>
        <span style="font-size:12px; display:block;">CRUD Area</span>
      </a>
      <a href="tarif.php" style="text-decoration:none; padding:14px 10px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--ink); font-weight:600; display:block;">
        <span class="stat-icon stat-icon-orange" style="margin:0 auto 8px; width:36px; height:36px;"><?= icon('tag') ?></span>
        <span style="font-size:12px; display:block;">Konfigurasi Tarif</span>
      </a>
      <a href="user.php" style="text-decoration:none; padding:14px 10px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--ink); font-weight:600; display:block;">
        <span class="stat-icon stat-icon-purple" style="margin:0 auto 8px; width:36px; height:36px;"><?= icon('users') ?></span>
        <span style="font-size:12px; display:block;">Manajemen User</span>
      </a>
      <a href="analitik.php" style="text-decoration:none; padding:14px 10px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--ink); font-weight:600; display:block;">
        <span class="stat-icon stat-icon-blue" style="margin:0 auto 8px; width:36px; height:36px;"><?= icon('chart') ?></span>
        <span style="font-size:12px; display:block;">Laporan Analitik</span>
      </a>
      <a href="log.php" style="text-decoration:none; padding:14px 10px; text-align:center; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); color:var(--ink); font-weight:600; display:block;">
        <span class="stat-icon stat-icon-green" style="margin:0 auto 8px; width:36px; height:36px;"><?= icon('activity') ?></span>
        <span style="font-size:12px; display:block;">Audit Log</span>
      </a>
    </div>
  </div>
</div>

<!-- FULL-WIDTH AUDIT LOG ACTIVITY FEED (PANJANG & LEBAR) -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
  <h3 style="font-size:18px; margin:0; display:flex; align-items:center; gap:8px;">
    <?= icon('activity') ?> Log Aktivitas Sistem Terakhir (Full Audit Feed)
  </h3>
  <a href="log.php" style="font-size:13px; color:var(--accent-dark); font-weight:600; text-decoration:none;">Buka Audit Log Lengkap →</a>
</div>

<div class="card" style="padding:20px; margin-bottom:32px;">
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:14px;">
    <?php foreach ($logTerbaru as $log): ?>
      <div style="display:flex; align-items:start; gap:12px; padding:12px 14px; background:var(--surface-alt); border:1px solid var(--border); border-radius:var(--radius); border-left:4px solid var(--accent);">
        <div style="width:34px; height:34px; border-radius:50%; background:var(--accent-soft); color:var(--accent-dark); font-weight:800; font-size:13px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
          <?= strtoupper(substr($log['nama_lengkap'], 0, 1)) ?>
        </div>
        <div style="flex:1; overflow:hidden;">
          <div style="display:flex; justify-content:space-between; align-items:baseline; gap:8px;">
            <strong style="font-size:13px; color:var(--ink);"><?= htmlspecialchars($log['nama_lengkap']) ?></strong>
            <span style="font-size:11px; color:var(--ink-muted); font-family:var(--font-mono);"><?= htmlspecialchars(date('d M H:i', strtotime($log['waktu_aktivitas']))) ?></span>
          </div>
          <p style="margin:4px 0 0; font-size:12.5px; color:var(--ink-muted); line-height:1.4; word-break:break-word;">
            <?= htmlspecialchars($log['aktivitas']) ?>
          </p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Live Spot List Kendaraan Sedang Parkir -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
  <h3 style="font-size:18px; margin:0;">Spot List — Kendaraan Sedang Terparkir Real-Time (<?= count($sesiAktif) ?>)</h3>
  <a href="../public/lacak.php" target="_blank" style="font-size:13px; color:var(--accent-dark); font-weight:600; text-decoration:none;">Buka Lacak Publik ↗</a>
</div>

<div class="card" style="padding:0; overflow:hidden;">
  <div style="overflow-x:auto;">
    <table style="width:100%;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Plat Nomor</th>
          <th>Jenis</th>
          <th>Warna Kendaraan</th>
          <th>Area Parkir</th>
          <th>Waktu Masuk</th>
          <th>Durasi Berjalan</th>
          <th>Estimasi Biaya</th>
          <th>Aksi Kendaraan Keluar</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sesiAktif as $s):
          $durasi = hitungDurasiJam($s['waktu_masuk'], date('Y-m-d H:i:s'));
          $estimasi = hitungBiaya($durasi, $s['tarif_per_jam']);
        ?>
        <tr>
          <td>#<?= $s['id_parkir'] ?></td>
          <td><strong style="font-family:var(--font-mono); font-size:14px;"><?= htmlspecialchars($s['plat_nomor']) ?></strong></td>
          <td><span class="badge badge-success"><?= htmlspecialchars(ucfirst($s['jenis_kendaraan'])) ?></span></td>
          <td><?= htmlspecialchars($s['warna'] ?: '-') ?></td>
          <td><strong><?= htmlspecialchars($s['nama_area']) ?></strong></td>
          <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($s['waktu_masuk']))) ?></td>
          <td><strong><?= $durasi ?> jam</strong></td>
          <td><strong style="color:var(--accent-dark); font-size:15px;"><?= formatRupiah($estimasi) ?></strong></td>
          <td style="white-space:nowrap;">
            <a href="dashboard.php?aksi=keluar&id_parkir=<?= $s['id_parkir'] ?>" onclick="return confirm('Proses KELUAR untuk kendaraan <?= htmlspecialchars($s['plat_nomor']) ?>? Total biaya estimasi: <?= formatRupiah($estimasi) ?>')" class="btn-add" style="padding:6px 12px; font-size:12px; background:#D97706; text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
              🚪 Proses Keluar
            </a>
            <a href="struk.php?id=<?= $s['id_parkir'] ?>" target="_blank" class="btn btn-edit" style="font-size:12px; padding:6px 10px; text-decoration:none;">
              🖨️ Struk
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$sesiAktif): ?>
          <tr><td colspan="9" style="text-align:center; color:var(--ink-muted); padding:28px;">Tidak ada kendaraan yang sedang terparkir saat ini.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
