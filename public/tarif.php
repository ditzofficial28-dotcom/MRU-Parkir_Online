<?php
/**
 * Modul  : Tarif Parkir (Publik)
 * Proses : SELECT semua baris tb_tarif, data yang sama persis dengan yang
 *          dikelola admin lewat menu CRUD Tarif Parkir
 * Output : Kartu tarif per jenis kendaraan dengan desain terpadu MRu-Parkir
 */
require_once __DIR__ . '/../includes/functions.php';

$tarifList = $pdo->query("SELECT * FROM tb_tarif ORDER BY tarif_per_jam ASC")->fetchAll();

$judulHalaman = 'Tarif Parkir';
require __DIR__ . '/../includes/header_public.php';
?>

<section class="pub-section">
  <span class="pub-eyebrow">Transparan &amp; Terkini</span>
  <h2 class="pub-section-title">Daftar Tarif Parkir Resmi</h2>
  <p class="pub-note" style="margin-bottom:32px;">Perhitungan tarif transparan per jam tanpa biaya siluman. Data berikut tersinkronisasi secara real-time dengan sistem admin MRu-Parkir.</p>

  <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; margin-bottom: 40px;">
    <?php foreach ($tarifList as $t): 
      $jenis = strtolower($t['jenis_kendaraan']);
      $iconClass = 'stat-icon-blue';
      $iconName = 'car';
      if (strpos($jenis, 'motor') !== false || strpos($jenis, 'sepeda') !== false) {
        $iconClass = 'stat-icon-green';
        $iconName = 'activity';
      } else if (strpos($jenis, 'truk') !== false || strpos($jenis, 'bus') !== false) {
        $iconClass = 'stat-icon-purple';
        $iconName = 'tag';
      }
    ?>
      <div class="pricing-card" style="padding: 32px 24px; text-align: left; position: relative;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 20px;">
          <span class="stat-icon <?= $iconClass ?>"><?= icon($iconName) ?></span>
          <span class="badge badge-success">Resmi &amp; Flat / Jam</span>
        </div>
        <span class="pricing-jenis" style="font-size: 13px; font-weight: 700; color: var(--ink); text-transform: uppercase; letter-spacing: .08em; display: block; margin-bottom: 6px;"><?= htmlspecialchars(ucfirst($t['jenis_kendaraan'])) ?></span>
        <div style="display: flex; align-items: baseline; gap: 6px; margin-bottom: 8px;">
          <span class="pricing-amount" style="font-size: 32px; font-weight: 800; color: var(--accent-dark);"><?= formatRupiah($t['tarif_per_jam']) ?></span>
          <span class="pricing-unit" style="font-size: 13px; color: var(--ink-muted); font-weight: 500;">/ jam</span>
        </div>
        <p style="font-size: 12.5px; color: var(--ink-muted); margin: 0; line-height: 1.5;">Berlaku untuk seluruh area parkir terintegrasi MRu-Parkir.</p>
      </div>
    <?php endforeach; ?>
    <?php if (!$tarifList): ?>
      <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--ink-muted);">
        Belum ada data tarif parkir yang dikonfigurasi.
      </div>
    <?php endif; ?>
  </div>

  <!-- Catatan Ketentuan Pembayaran -->
  <div class="card" style="background: var(--surface-alt); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 28px;">
    <div style="display: flex; gap: 16px; align-items: flex-start;">
      <span class="stat-icon stat-icon-orange"><?= icon('tag') ?></span>
      <div>
        <h4 style="font-size: 15.5px; margin-bottom: 6px;">Informasi Ketentuan Pembayaran</h4>
        <p style="margin: 0; font-size: 13.5px; color: var(--ink-muted); line-height: 1.6;">
          • Durasi parkir dihitung otomatis saat kendaraan masuk gerbang parkir.<br>
          • Pembulatan waktu dilakukan ke atas per jam berikutnya.<br>
          • Harap simpan bukti pembayaran / karcis parkir untuk verifikasi saat kendaraan keluar.
        </p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer_public.php'; ?>
