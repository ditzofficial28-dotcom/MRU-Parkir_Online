<?php
/**
 * Modul  : Lacak Kendaraan & Cek Lokasi (Publik)
 * Input  : plat_nomor (GET), pemilik (GET), jenis_kendaraan (GET), warna (GET), area (GET)
 * Proses : Cek ketersediaan area parkir dan/atau cari lokasi, warna, pemilik & estimasi biaya kendaraan.
 * Output : Kartu ketersediaan area lokasi parkir & status kendaraan dengan rincian lengkap.
 */
require_once __DIR__ . '/../includes/functions.php';

$plat    = amankanInput($_GET['plat_nomor'] ?? '');
$jenis   = amankanInput($_GET['jenis_kendaraan'] ?? '');
$warna   = amankanInput($_GET['warna'] ?? '');
$id_area = (int) ($_GET['area'] ?? 0);

$areaList = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area")->fetchAll();

$areaInfo = null;
$listKendaraanArea = [];
if ($id_area > 0) {
    $stmtArea = $pdo->prepare("SELECT * FROM tb_area_parkir WHERE id_area = ?");
    $stmtArea->execute([$id_area]);
    $areaInfo = $stmtArea->fetch();

    if ($areaInfo) {
        $stmtParkir = $pdo->prepare(
            "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna 
             FROM tb_transaksi t 
             JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan 
             WHERE t.id_area = ? AND t.status = 'masuk' 
             ORDER BY t.waktu_masuk DESC LIMIT 10"
        );
        $stmtParkir->execute([$id_area]);
        $listKendaraanArea = $stmtParkir->fetchAll();
    }
}

$hasSearch = ($plat !== '' || $jenis !== '' || $warna !== '');
$hasil = null;
$tidakDitemukan = false;

if ($hasSearch) {
    $queryWhere = ["1=1"];
    $params = [];

    if ($plat !== '') {
        $queryWhere[] = "k.plat_nomor LIKE ?";
        $params[] = "%{$plat}%";
    }
    if ($jenis !== '') {
        $queryWhere[] = "k.jenis_kendaraan = ?";
        $params[] = $jenis;
    }
    if ($warna !== '') {
        $queryWhere[] = "k.warna LIKE ?";
        $params[] = "%{$warna}%";
    }
    if ($id_area > 0) {
        $queryWhere[] = "t.id_area = ?";
        $params[] = $id_area;
    }

    $whereSql = implode(" AND ", $queryWhere);
    $stmt = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, k.warna, a.nama_area, tr.tarif_per_jam
         FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         JOIN tb_area_parkir a ON a.id_area = t.id_area
         JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
         WHERE {$whereSql}
         ORDER BY t.waktu_masuk DESC LIMIT 1"
    );
    $stmt->execute($params);
    $hasil = $stmt->fetch();
    $tidakDitemukan = !$hasil;

    if ($hasil && $hasil['status'] === 'masuk') {
        $durasiBerjalan = hitungDurasiJam($hasil['waktu_masuk'], date('Y-m-d H:i:s'));
        $estimasiBiaya  = hitungBiaya($durasiBerjalan, $hasil['tarif_per_jam']);
    }
}

$judulHalaman = 'Lacak Kendaraan & Cek Lokasi';
require __DIR__ . '/../includes/header_public.php';
?>

<section class="pub-section">
  <span class="pub-eyebrow">Pencarian Terpadu</span>
  <h2 class="pub-section-title">Lacak Kendaraan &amp; Cek Lokasi Parkir</h2>
  <p class="pub-note" style="margin-bottom:24px;">Lengkapi data kendaraan (Plat Nomor, Jenis, dan Warna) untuk melacak status tiket digital dan lokasi parkir Anda.</p>

  <!-- Form Pencarian Kendaraan Terpisah & Wajib Diisi -->
  <form method="get" class="card lacak-form" style="margin-bottom:28px; max-width: 100%;">
    <div style="margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <div>
        <strong style="font-size:15px; color:var(--ink);">Form Pencarian Kendaraan</strong>
        <small style="display:block; color:var(--ink-muted); font-size:12px; margin-top:2px;">Masukkan Plat Nomor Kendaraan untuk melacak lokasi &amp; status parkir.</small>
      </div>

      <div>
        <select name="area" id="f-area" onchange="this.form.submit()" style="padding:8px 34px 8px 12px; font-size:12.5px;">
          <option value="">Filter Area: Semua Area</option>
          <?php foreach ($areaList as $a): 
            $sisa = (int)$a['kapasitas'] - (int)$a['terisi'];
          ?>
            <option value="<?= $a['id_area'] ?>" <?= $id_area === (int) $a['id_area'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['nama_area']) ?> (Sisa <?= $sisa ?> slot)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="lacak-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end;">
      <div>
        <label for="f-plat" style="margin-top:0;">Plat Nomor Kendaraan <span style="color:var(--danger);">*</span></label>
        <input type="text" name="plat_nomor" id="f-plat" placeholder="B 1234 ABC" value="<?= htmlspecialchars($plat) ?>" required>
      </div>

      <div>
        <label for="f-jenis" style="margin-top:0;">Jenis Kendaraan <span style="color:var(--danger);">*</span></label>
        <?php
          $jenisIconMap = [
            'mobil'   => ['icon' => 'car',   'label' => 'Mobil'],
            'motor'   => ['icon' => 'motor', 'label' => 'Motor'],
            'lainnya' => ['icon' => 'truck', 'label' => 'Lainnya / Bus / Truk']
          ];
          $cVal = $jenis;
          $cIcon = $jenisIconMap[$cVal]['icon'] ?? 'tag';
          $cLabel = $jenisIconMap[$cVal]['label'] ?? '-- Pilih Jenis --';
        ?>
        <div class="custom-select-wrapper" id="customJenisWrapper">
          <input type="hidden" name="jenis_kendaraan" id="f-jenis" value="<?= htmlspecialchars($jenis) ?>" required>
          <div class="custom-select-trigger" tabindex="0">
            <span class="custom-select-icon" id="jenisSelectedIcon"><?= icon($cIcon) ?></span>
            <span class="custom-select-text" id="jenisSelectedText"><?= htmlspecialchars($cLabel) ?></span>
            <span class="custom-select-arrow"><?= icon('chevron') ?></span>
          </div>
          <div class="custom-select-dropdown">
            <div class="custom-option <?= $jenis === '' ? 'selected' : '' ?>" data-value="" data-icon="tag" data-label="-- Pilih Jenis --">
              <span class="custom-option-icon"><?= icon('tag') ?></span>
              <span class="custom-option-text">-- Pilih Jenis --</span>
            </div>
            <div class="custom-option <?= $jenis === 'mobil' ? 'selected' : '' ?>" data-value="mobil" data-icon="car" data-label="Mobil">
              <span class="custom-option-icon"><?= icon('car') ?></span>
              <span class="custom-option-text">Mobil</span>
            </div>
            <div class="custom-option <?= $jenis === 'motor' ? 'selected' : '' ?>" data-value="motor" data-icon="motor" data-label="Motor">
              <span class="custom-option-icon"><?= icon('motor') ?></span>
              <span class="custom-option-text">Motor</span>
            </div>
            <div class="custom-option <?= $jenis === 'lainnya' ? 'selected' : '' ?>" data-value="lainnya" data-icon="truck" data-label="Lainnya / Bus / Truk">
              <span class="custom-option-icon"><?= icon('truck') ?></span>
              <span class="custom-option-text">Lainnya / Bus / Truk</span>
            </div>
          </div>
        </div>
      </div>

      <div>
        <label for="f-warna" style="margin-top:0;">Warna Kendaraan <span style="color:var(--danger);">*</span></label>
        <input type="text" name="warna" id="f-warna" placeholder="Hitam Metalik" value="<?= htmlspecialchars($warna) ?>" required>
      </div>
    </div>

    <div style="display:flex; gap:10px; margin-top:18px; justify-content:flex-end; align-items:center; flex-wrap:wrap;">
      <?php if ($hasSearch || $id_area > 0): ?>
        <a href="lacak.php" class="pub-cta-ghost" style="padding: 10px 16px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); text-decoration: none; color: var(--ink-muted); background: var(--surface-alt); font-size: 13px; font-weight: 600;">
          ✕ Reset Form
        </a>
      <?php endif; ?>

      <button type="submit" class="btn-add" style="padding:11px 24px; font-size:14px; justify-content:center;">
        <?= icon('search') ?> Lacak Kendaraan Sekarang
      </button>
    </div>
  </form>

  <?php if ($hasSearch && $tidakDitemukan): ?>
    <div class="alert">Data kendaraan yang Anda cari tidak ditemukan dalam riwayat parkir aktif. Pastikan Plat Nomor, Pemilik, Jenis, dan Warna sudah sesuai.</div>
  <?php endif; ?>

  <!-- Hasil Lacak Kendaraan Lengkap -->
  <?php if ($hasil): ?>
    <div class="card lacak-result" style="margin-bottom:28px; max-width: 100%; border: 2px solid var(--accent);">
      <div class="avail-card-head" style="flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <div>
          <span class="pub-eyebrow" style="margin-bottom:2px;">Hasil Lacak Rincian Kendaraan</span>
          <h3 style="margin:0; font-size:20px; font-family:var(--font-mono);"><?= htmlspecialchars($hasil['plat_nomor']) ?></h3>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
          <?php if ($hasil['status'] === 'masuk'): ?>
            <span class="badge badge-warning" style="font-size:12.5px; padding:6px 14px;">Sedang Parkir Aktif</span>
          <?php else: ?>
            <span class="badge badge-success" style="font-size:12.5px; padding:6px 14px;">Sudah Keluar</span>
          <?php endif; ?>
          
          <a href="tiket.php?id=<?= $hasil['id_parkir'] ?>" target="_blank" class="btn-add" style="font-size:12.5px; padding:7px 16px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
            <?= icon('ticket') ?> Buka E-Karcis Digital ↗
          </a>
        </div>
      </div>

      <div class="lacak-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; padding-top: 14px; border-top: 1px solid var(--border);">
        <div>
          <span>Plat Nomor Kendaraan</span>
          <strong style="font-family:var(--font-mono); font-size:17px; color:var(--ink);"><?= htmlspecialchars($hasil['plat_nomor']) ?></strong>
        </div>

        <div>
          <span>Jenis Kendaraan</span>
          <div>
            <span class="badge badge-success" style="font-size:12px; margin-top:2px;">
              <?= htmlspecialchars(ucfirst($hasil['jenis_kendaraan'])) ?>
            </span>
          </div>
        </div>

        <div>
          <span>Warna Kendaraan</span>
          <strong style="font-size:14.5px; color:var(--ink);"><?= htmlspecialchars($hasil['warna'] ?: 'Tidak Tercatat') ?></strong>
        </div>

        <div>
          <span>Lokasi Area Parkir</span>
          <strong style="font-size:14.5px; color:var(--accent-dark);"><?= htmlspecialchars($hasil['nama_area']) ?></strong>
        </div>

        <div>
          <span>Waktu Masuk</span>
          <strong style="font-family:var(--font-mono); font-size:14px;"><?= htmlspecialchars(date('d M Y, H:i:s', strtotime($hasil['waktu_masuk']))) ?></strong>
        </div>

        <?php if ($hasil['status'] === 'masuk'): ?>
          <div>
            <span>Durasi Berjalan</span>
            <strong style="font-family:var(--font-mono); font-size:15px; color:var(--ink);"><?= $durasiBerjalan ?> jam</strong>
          </div>
          <div>
            <span>Estimasi Biaya Berjalan</span>
            <strong style="font-family:var(--font-display); font-size:20px; color:var(--accent-dark);"><?= formatRupiah($estimasiBiaya) ?></strong>
          </div>
        <?php else: ?>
          <div>
            <span>Waktu Keluar</span>
            <strong style="font-family:var(--font-mono); font-size:14px;"><?= htmlspecialchars(date('d M Y, H:i:s', strtotime($hasil['waktu_keluar']))) ?></strong>
          </div>
          <div>
            <span>Total Pembayaran Parkir</span>
            <strong style="font-family:var(--font-display); font-size:20px; color:var(--accent-dark);"><?= formatRupiah($hasil['biaya_total']) ?></strong>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Detail Area Terpilih -->
  <?php if ($areaInfo): ?>
    <?php 
      $sisaArea = (int)$areaInfo['kapasitas'] - (int)$areaInfo['terisi'];
      $pctArea = $areaInfo['kapasitas'] > 0 ? min(100, round($areaInfo['terisi'] / $areaInfo['kapasitas'] * 100)) : 0;
    ?>
    <div class="card lacak-result" style="margin-bottom:32px; max-width: 100%; border: 2px solid var(--accent);">
      <div class="avail-card-head" style="margin-bottom:14px; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
        <div>
          <span class="pub-eyebrow" style="margin-bottom:2px;">Detail Area Parkir</span>
          <h3 style="font-size:20px; margin:0;"><?= htmlspecialchars($areaInfo['nama_area']) ?></h3>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
          <span class="badge <?= $sisaArea <= 0 ? 'badge-danger' : ($pctArea >= 60 ? 'badge-warning' : 'badge-success') ?>" style="font-size: 13px; padding: 6px 14px;">
            <?= $sisaArea <= 0 ? 'Penuh' : $sisaArea . ' slot tersisa' ?>
          </span>
          <a href="lacak.php" class="btn-add" style="background: var(--surface-alt); color: var(--ink); border: 1px solid var(--border); padding: 8px 16px; font-size: 13px; text-decoration: none; font-weight: 600;">
            ← Kembali ke Semua Area
          </a>
        </div>
      </div>
      <div class="occupancy-bar" style="height:12px; margin-bottom:12px;">
        <div class="occupancy-fill <?= $pctArea >= 90 ? 'fill-danger' : ($pctArea >= 60 ? 'fill-warning' : 'fill-success') ?>" style="width:<?= $pctArea ?>%"></div>
      </div>
      <div class="avail-card-foot" style="font-size:13px;">
        <span><strong><?= $areaInfo['terisi'] ?></strong> slot terisi</span>
        <span><strong><?= $areaInfo['kapasitas'] ?></strong> kapasitas total</span>
      </div>

      <?php if ($listKendaraanArea): ?>
        <div style="margin-top:20px; padding-top:16px; border-top:1px solid var(--border);">
          <strong style="display:block; font-size:13.5px; margin-bottom:12px;">Daftar Kendaraan Sedang Parkir di Area Ini (<?= count($listKendaraanArea) ?>):</strong>
          <div style="overflow-x: auto;">
            <table style="font-size:12.5px; width: 100%;">
              <thead>
                <tr>
                  <th>Plat Nomor</th>
                  <th>Jenis Kendaraan</th>
                  <th>Warna</th>
                  <th>Waktu Masuk</th>
                  <th>E-Karcis</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($listKendaraanArea as $k): ?>
                  <tr>
                    <td><strong style="font-family:var(--font-mono); font-size:13.5px;"><?= htmlspecialchars($k['plat_nomor']) ?></strong></td>
                    <td><span class="badge badge-success"><?= htmlspecialchars(ucfirst($k['jenis_kendaraan'])) ?></span></td>
                    <td><?= htmlspecialchars($k['warna'] ?: '-') ?></td>
                    <td><?= htmlspecialchars(date('H:i, d M Y', strtotime($k['waktu_masuk']))) ?></td>
                    <td>
                      <a href="tiket.php?id=<?= $k['id_parkir'] ?>" target="_blank" style="color:var(--accent-dark); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        <?= icon('ticket') ?> E-Karcis ↗
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php else: ?>
        <div style="margin-top:16px; padding: 14px; background: var(--surface-alt); border-radius: var(--radius-sm); font-size: 13px; color: var(--ink-muted); text-align: center;">
          Belum ada kendaraan yang sedang terparkir aktif di area ini.
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Ringkasan Ketersediaan Lokasi Parkir (Tampilan Card Statis) -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin: 28px 0 16px;">
    <h3 style="font-size:18px; margin:0;">
      Ringkasan Ketersediaan Lokasi Parkir
    </h3>
  </div>

  <div class="avail-grid">
    <?php foreach ($areaList as $a): 
      $pct = $a['kapasitas'] > 0 ? min(100, round($a['terisi'] / $a['kapasitas'] * 100)) : 0; 
      $sisa = $a['kapasitas'] - $a['terisi']; 
      $isCurrent = $id_area === (int) $a['id_area'];
    ?>
      <div class="avail-card" style="cursor: default; pointer-events: none; user-select: none; <?= $isCurrent ? 'border: 2px solid var(--accent); background: rgba(16, 185, 129, 0.05);' : '' ?>">
        <div class="avail-card-head">
          <h3 style="font-size: 15px; font-weight: 700;"><?= htmlspecialchars($a['nama_area']) ?></h3>
          <span class="badge <?= $sisa <= 0 ? 'badge-danger' : ($pct >= 60 ? 'badge-warning' : 'badge-success') ?>">
            <?= $sisa <= 0 ? 'Penuh' : $sisa . ' slot' ?>
          </span>
        </div>
        <div class="occupancy-bar" style="height:10px;">
          <div class="occupancy-fill <?= $pct >= 90 ? 'fill-danger' : ($pct >= 60 ? 'fill-warning' : 'fill-success') ?>" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="avail-card-foot">
          <span><?= $a['terisi'] ?> terisi</span>
          <span><?= $a['kapasitas'] ?> kapasitas</span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const wrapper = document.getElementById('customJenisWrapper');
  if (!wrapper) return;
  
  const trigger = wrapper.querySelector('.custom-select-trigger');
  const dropdown = wrapper.querySelector('.custom-select-dropdown');
  const options = wrapper.querySelectorAll('.custom-option');
  const hiddenInput = document.getElementById('f-jenis');
  const selectedIcon = document.getElementById('jenisSelectedIcon');
  const selectedText = document.getElementById('jenisSelectedText');

  const iconsMap = {
    'tag': `<?= icon('tag') ?>`,
    'car': `<?= icon('car') ?>`,
    'motor': `<?= icon('motor') ?>`,
    'truck': `<?= icon('truck') ?>`
  };

  trigger.addEventListener('click', function(e) {
    e.stopPropagation();
    wrapper.classList.toggle('open');
  });

  trigger.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      wrapper.classList.toggle('open');
    }
  });

  options.forEach(opt => {
    opt.addEventListener('click', function(e) {
      e.stopPropagation();
      const val = this.getAttribute('data-value');
      const iconKey = this.getAttribute('data-icon');
      const labelText = this.getAttribute('data-label');

      hiddenInput.value = val;
      selectedIcon.innerHTML = iconsMap[iconKey] || iconsMap['tag'];
      selectedText.textContent = labelText;

      options.forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');

      wrapper.classList.remove('open');
      wrapper.classList.remove('error');
    });
  });

  document.addEventListener('click', function() {
    wrapper.classList.remove('open');
  });

  const form = wrapper.closest('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      if (!hiddenInput.value) {
        e.preventDefault();
        wrapper.classList.add('error');
        trigger.focus();
      }
    });
  }
});
</script>

<?php require __DIR__ . '/../includes/footer_public.php'; ?>
