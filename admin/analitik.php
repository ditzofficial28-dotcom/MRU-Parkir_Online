<?php
/**
 * Modul  : Analitik Kinerja — khusus role admin
 * Proses : Hitung okupansi % tiap area untuk grafik batang (Chart.js),
 *          serta ringkasan performa transaksi bulan berjalan
 * Output : grafik batang okupansi per area, tabel detail lantai & lokasi
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

$areaList = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area")->fetchAll();
$labelArea = []; $dataOkupansi = [];
foreach ($areaList as $a) {
    $labelArea[] = $a['nama_area'];
    $dataOkupansi[] = $a['kapasitas'] > 0 ? round($a['terisi'] / $a['kapasitas'] * 100, 1) : 0;
}

$bulanIni = $pdo->query(
    "SELECT COUNT(*) c, COALESCE(SUM(biaya_total),0) s, COALESCE(AVG(durasi_jam),0) d
     FROM tb_transaksi WHERE status='keluar' AND MONTH(waktu_keluar)=MONTH(CURDATE()) AND YEAR(waktu_keluar)=YEAR(CURDATE())"
)->fetch();

$judulHalaman = 'Analitik Kinerja';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-icon stat-icon-green"><?= icon('chart') ?></span>
    <div><span class="stat-value" style="font-size:19px;"><?= formatRupiah($bulanIni['s']) ?></span><span class="stat-label">Pendapatan Bulan Ini</span></div>
  </div>
  <div class="stat-card">
    <span class="stat-icon stat-icon-blue"><?= icon('ticket') ?></span>
    <div><span class="stat-value"><?= (int) $bulanIni['c'] ?></span><span class="stat-label">Transaksi Bulan Ini</span></div>
  </div>
  <div class="stat-card">
    <span class="stat-icon stat-icon-orange"><?= icon('activity') ?></span>
    <div><span class="stat-value"><?= round($bulanIni['d'], 1) ?> j</span><span class="stat-label">Rata-rata Durasi Parkir</span></div>
  </div>
</div>

<div class="card chart-card">
  <h3 style="margin-top:0;">Okupansi per Lantai / Area</h3>
  <canvas id="chartOkupansi" height="90"></canvas>
</div>

<h3>Lantai &amp; Lokasi Detail</h3>
<table>
<tr><th>Area</th><th>Total Kapasitas</th><th>Terisi</th><th>Okupansi</th></tr>
<?php foreach ($areaList as $a): $pct = $a['kapasitas'] > 0 ? round($a['terisi'] / $a['kapasitas'] * 100) : 0; ?>
<tr>
  <td><?= htmlspecialchars($a['nama_area']) ?></td>
  <td><?= $a['kapasitas'] ?></td>
  <td><?= $a['terisi'] ?></td>
  <td><span class="badge <?= $pct >= 90 ? 'badge-danger' : ($pct >= 60 ? 'badge-warning' : 'badge-success') ?>"><?= $pct ?>%</span></td>
</tr>
<?php endforeach; ?>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartOkupansi'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labelArea) ?>,
    datasets: [{
      label: 'Okupansi (%)',
      data: <?= json_encode($dataOkupansi) ?>,
      backgroundColor: '#10B981',
      borderRadius: 6,
      maxBarThickness: 46
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' }, grid: { color: '#EEF1F5' } },
      x: { grid: { display: false } }
    }
  }
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
