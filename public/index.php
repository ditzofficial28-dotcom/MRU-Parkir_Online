<?php
/**
 * Modul  : Beranda Publik (MRu-Parking)
 * Proses : Ambil ringkasan live dari database yang sama dengan backend admin
 *          (total area, sisa slot, tarif termurah) untuk hero & search card
 * Output : halaman landing lengkap: hero, fitur, cara kerja, promo aplikasi
 */
require_once __DIR__ . '/../includes/functions.php';

$areaList = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area")->fetchAll();
$rekap = $pdo->query("SELECT COALESCE(SUM(kapasitas),0) k, COALESCE(SUM(terisi),0) t FROM tb_area_parkir")->fetch();
$sisaSlot = (int) $rekap['k'] - (int) $rekap['t'];
$tarifList = $pdo->query("SELECT * FROM tb_tarif ORDER BY tarif_per_jam ASC")->fetchAll();
$tarifTermurah = $tarifList[0]['tarif_per_jam'] ?? 0;

$judulHalaman = 'Beranda';
require __DIR__ . '/../includes/header_public.php';
?>

<section class="pub-hero">
  <div class="pub-hero-text">
    <span class="pub-eyebrow">Parkir Cerdas untuk Semua</span>
    <h1>Parkir Mudah,<br><span style="color:var(--accent);">Cepat &amp; Aman</span></h1>
    <p>Solusi parkir modern untuk pengalaman yang nyaman, aman, tanpa ribet.
       Temukan lokasi parkir terbaik dan kelola parkir Anda dengan mudah.</p>
    <div class="pub-hero-cta">
      <a href="#unduh" id="unduh" class="btn-add pub-cta-lg"><?= icon('car') ?> Unduh Aplikasi</a>
      <a href="#cara-kerja" class="pub-cta-ghost">Cara Kerja →</a>
    </div>
    <div class="pub-trust-row">
      <span><?= icon('tag') ?> Pembayaran Praktis</span>
      <span><?= icon('activity') ?> Keamanan Terjamin</span>
    </div>
  </div>

  <div class="pub-hero-visual">
    <div class="hero-photo-wrap">
      <img
        src="../assets/hero_parking.png"
        alt="Area parkir modern MRu-Parkir"
        class="pub-hero-img"
        loading="eager"
      >
      <div class="hero-logo-badge">
        <img src="../assets/logo_mru.jpg" alt="Logo MRu-Parkir" class="hero-badge-img">
        <div class="hero-badge-text">
          <span class="hero-badge-name">MRu-Parkir</span>
          <span class="hero-badge-sub">Smart Parking System</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="pub-section pub-section-alt" id="unduh">
  <div class="app-download-wrap" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 48px; align-items: center;">
    <div class="app-download-text">
      <span class="pub-eyebrow">Aplikasi Mobile Resmi</span>
      <h2 class="pub-section-title" style="font-size: 30px; line-height: 1.25; margin-bottom: 14px;">Unduh MRu-Parkir Mobile Untuk Pengalaman Parkir Lebih Cerdas</h2>
      <p class="pub-note" style="font-size: 14.5px; margin-bottom: 28px; line-height: 1.6;">Nikmati kemudahan memantau sisa slot parkir secara real-time, melacak tiket kendaraan aktif, dan melakukan pembayaran instan tanpa antre langsung dari smartphone Anda.</p>
      
      <div class="app-features-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 32px;">
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <span class="stat-icon stat-icon-green" style="width: 38px; height: 38px; font-size: 14px; flex-shrink: 0;"><?= icon('dashboard') ?></span>
          <div>
            <strong style="font-size: 13.5px; display: block; margin-bottom: 2px;">Cek Slot Real-Time</strong>
            <span style="font-size: 12px; color: var(--ink-muted); line-height: 1.4; display: block;">Pantau sisa kuota parkir sebelum Anda tiba.</span>
          </div>
        </div>
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <span class="stat-icon stat-icon-blue" style="width: 38px; height: 38px; font-size: 14px; flex-shrink: 0;"><?= icon('tag') ?></span>
          <div>
            <strong style="font-size: 13.5px; display: block; margin-bottom: 2px;">Bayar Cashless</strong>
            <span style="font-size: 12px; color: var(--ink-muted); line-height: 1.4; display: block;">Dukungan Saldo E-Parkir &amp; QRIS praktis.</span>
          </div>
        </div>
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <span class="stat-icon stat-icon-orange" style="width: 38px; height: 38px; font-size: 14px; flex-shrink: 0;"><?= icon('car') ?></span>
          <div>
            <strong style="font-size: 13.5px; display: block; margin-bottom: 2px;">Lacak Tiket Aktif</strong>
            <span style="font-size: 12px; color: var(--ink-muted); line-height: 1.4; display: block;">Cek durasi &amp; estimasi tarif berjalan.</span>
          </div>
        </div>
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <span class="stat-icon stat-icon-purple" style="width: 38px; height: 38px; font-size: 14px; flex-shrink: 0;"><?= icon('ticket') ?></span>
          <div>
            <strong style="font-size: 13.5px; display: block; margin-bottom: 2px;">E-Karcis Digital</strong>
            <span style="font-size: 12px; color: var(--ink-muted); line-height: 1.4; display: block;">Riwayat parkir rapi &amp; transparan.</span>
          </div>
        </div>
      </div>

      <div class="store-badges" style="justify-content: flex-start; margin-top: 0; flex-wrap: wrap;">
        <a href="https://play.google.com/store/apps" target="_blank" rel="noopener noreferrer" class="store-badge" style="padding: 11px 20px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;" title="Unduh di Google Play Store">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M3 20.5v-17c0-.55.45-1 1-1h.21l9.9 8.5-9.9 8.5H4c-.55 0-1-.45-1-1zm12.31-7.22L7.54 6.27l9.64 5.56c.45.26.45.92 0 1.18l-1.87 1.07zm1.87 1.07L7.54 19.73l7.77-6.38 1.87 1.07z"/></svg>
          <span>Google Play</span>
        </a>
        <a href="https://www.apple.com/app-store/" target="_blank" rel="noopener noreferrer" class="store-badge" style="padding: 11px 20px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;" title="Unduh di Apple App Store">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.32c.67-.82 1.13-1.96.99-3.11-.98.04-2.18.66-2.88 1.48-.63.73-1.18 1.9-.99 3.03 1.1.09 2.23-.57 2.88-1.4"/></svg>
          <span>App Store</span>
        </a>
      </div>
    </div>

    <div class="howitworks-phone">
      <div class="phone-mock-frame">
        <!-- iPhone Notch / Dynamic Island -->
        <div class="phone-dynamic-island"></div>
        
        <!-- Status Bar -->
        <div class="phone-status-bar">
          <span class="phone-time">09:41</span>
          <div class="phone-status-icons">
            <span>5G</span>
            <span>100%</span>
          </div>
        </div>

        <!-- Light Theme App Screen Content (Mirip Asli Foto User) -->
        <div class="phone-screen-content">
          <!-- App Header -->
          <div class="app-header-light">
            <div class="app-brand-light">
              <img src="../assets/logo_mru.jpg" alt="Logo" class="app-avatar-sm">
              <span class="app-logo-text">MRu-Parkir</span>
            </div>
            <span class="app-search-icon"><?= icon('search') ?></span>
          </div>

          <!-- Blue Saldo Card -->
          <div class="app-saldo-card-blue">
            <div class="saldo-blue-left">
              <span class="saldo-blue-label">Saldo</span>
              <div class="saldo-blue-amount">Rp 125.000</div>
            </div>
            <span class="saldo-topup-btn">Top Up</span>
          </div>

          <!-- 4 Round Quick Menu Icons -->
          <div class="app-quick-menu">
            <div class="quick-item">
              <span class="quick-icon icon-green"><?= icon('map') ?></span>
              <span>Parkir Aktif</span>
            </div>
            <div class="quick-item">
              <span class="quick-icon icon-blue"><?= icon('activity') ?></span>
              <span>Riwayat</span>
            </div>
            <div class="quick-item">
              <span class="quick-icon icon-pink"><?= icon('ticket') ?></span>
              <span>Favorit</span>
            </div>
            <div class="quick-item">
              <span class="quick-icon icon-orange"><?= icon('tag') ?></span>
              <span>Promo</span>
            </div>
          </div>

          <!-- Parkir Aktif Section -->
          <div class="app-active-section">
            <div class="active-sec-header">
              <strong>Parkir Aktif</strong>
            </div>
            
            <div class="active-parkir-card">
              <div class="active-location">Cibinong Mall</div>
              <div class="active-stats-grid">
                <div>
                  <span>Masuk</span>
                  <strong>10:15</strong>
                </div>
                <div>
                  <span>Durasi</span>
                  <strong>02:45:10</strong>
                </div>
                <div>
                  <span>Total</span>
                  <strong class="price-blue">Rp 4.200</strong>
                </div>
              </div>
              <button class="btn-perpanjang">Perpanjang</button>
            </div>
          </div>

          <!-- Lokasi Terdekat Section -->
          <div class="app-location-section">
            <div class="loc-sec-header">
              <strong>Lokasi Terdekat</strong>
              <span class="loc-see-all">Lihat Semua</span>
            </div>
            <div class="loc-mini-card">
              <span class="loc-badge-p">P</span>
              <div class="loc-mini-info">
                <strong>Plaza Cibinong Lt. 1</strong>
                <small>Sisa 45 slot • 500m</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Home Indicator Bar -->
        <div class="phone-home-bar" style="background:#CBD5E1;"></div>
      </div>
    </div>
  </div>
</section>

<!-- Section Cara Kerja (Alur 5 Langkah Di Bawahnya) -->
<section class="pub-section" id="cara-kerja" style="border-top: 1px solid var(--border);">
  <span class="pub-eyebrow" style="text-align: center; display: block;">Alur Pelayanan</span>
  <h2 class="pub-section-title" style="text-align: center; margin-bottom: 36px;">Cara Kerja MRu-Parkir</h2>
  
  <div class="steps-row" style="justify-content: center; max-width: 1000px; margin: 0 auto;">
    <div class="step-item">
      <span class="step-circle"><?= icon('users') ?></span>
      <strong>1. Datang</strong>
      <p>Menuju lokasi parkir tujuan Anda</p>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
      <span class="step-circle"><?= icon('car') ?></span>
      <strong>2. Masuk Area</strong>
      <p>Parkirkan kendaraan sesuai marka</p>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
      <span class="step-circle"><?= icon('activity') ?></span>
      <strong>3. Pencatatan</strong>
      <p>Petugas mencatat kendaraan Anda masuk</p>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
      <span class="step-circle"><?= icon('tag') ?></span>
      <strong>4. Pembayaran</strong>
      <p>Bayar sesuai tarif &amp; durasi parkir</p>
    </div>
    <div class="step-line"></div>
    <div class="step-item">
      <span class="step-circle"><?= icon('ticket') ?></span>
      <strong>5. Keluar</strong>
      <p>Struk dicetak, kendaraan keluar area</p>
    </div>
  </div>
</section>

<section class="pub-section" id="tentang">
  <span class="pub-eyebrow">Tarif Transparan</span>
  <h2 class="pub-section-title">Tarif Parkir</h2>
  <div class="pricing-grid">
    <?php foreach ($tarifList as $t): ?>
      <div class="pricing-card">
        <span class="pricing-jenis"><?= htmlspecialchars(ucfirst($t['jenis_kendaraan'])) ?></span>
        <span class="pricing-amount"><?= formatRupiah($t['tarif_per_jam']) ?></span>
        <span class="pricing-unit">per jam</span>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="pub-note">Lihat rincian lebih lanjut di halaman <a href="tarif.php">Tarif Parkir</a>. MRu-Parkir adalah
     sistem manajemen parkir yang dipercaya untuk mengelola area, tarif, dan transaksi secara terpadu.</p>
</section>

<?php require __DIR__ . '/../includes/footer_public.php'; ?>
