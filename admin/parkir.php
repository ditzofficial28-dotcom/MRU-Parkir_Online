<?php
/**
 * Modul  : Manajemen Parkir (hub) — khusus role admin
 * Proses : Tampilkan tiap area parkir sebagai "kartu lantai" (gaya P1/P2/P3
 *          pada mockup) lengkap dengan okupansi, lalu sediakan akses cepat
 *          ke CRUD Tarif Parkir dan CRUD Kendaraan
 * Output : grid kartu area + tombol aksi cepat
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

$areaList = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area")->fetchAll();

$judulHalaman = 'Manajemen Parkir';
require __DIR__ . '/../includes/header.php';
?>
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
  <p style="color:var(--ink-muted); margin:0;">Kelola area/lantai parkir, tarif, dan data kendaraan dari satu tempat.</p>
  <div style="display:flex; gap:10px;">
    <a href="tarif.php" class="btn-add"><?= icon('tag') ?> Kelola Tarif</a>
    <a href="kendaraan.php" class="btn-add"><?= icon('car') ?> Kelola Kendaraan</a>
  </div>
</div>

<h3 style="margin-top:0;">Lantai / Area Parkir</h3>
<div class="floor-grid">
  <?php foreach ($areaList as $a): $pct = $a['kapasitas'] > 0 ? min(100, round($a['terisi'] / $a['kapasitas'] * 100)) : 0; ?>
    <div class="floor-card">
      <span class="floor-badge"><?= icon('map') ?></span>
      <div class="floor-info">
        <div class="floor-name"><?= htmlspecialchars($a['nama_area']) ?></div>
        <div class="floor-sub">Okupansi <?= $pct ?>%</div>
        <div class="occupancy-bar" style="margin-top:8px;">
          <div class="occupancy-fill <?= $pct >= 90 ? 'fill-danger' : ($pct >= 60 ? 'fill-warning' : 'fill-success') ?>" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="floor-stats">
          <div><span>Total</span><strong><?= $a['kapasitas'] ?></strong></div>
          <div><span>Terisi</span><strong><?= $a['terisi'] ?></strong></div>
          <div><span>Sisa</span><strong><?= $a['kapasitas'] - $a['terisi'] ?></strong></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$areaList): ?><p>Belum ada data area. <a href="area.php">Tambah area parkir</a>.</p><?php endif; ?>
</div>

<h3>Kelola Data Area</h3>
<div class="card">
  <p style="margin:0 0 14px; color:var(--ink-muted); font-size:13.5px;">Tambah, ubah, atau hapus lantai/area parkir (kapasitas &amp; jumlah slot).</p>
  <a href="area.php" class="btn-add"><?= icon('plus') ?> Buka CRUD Area Parkir</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
