<?php
/**
 * Modul  : Tentang Kami / Profil Perusahaan (Publik)
 * Proses : Landing page resmi PT MRu-Parking Teknologi Indonesia — profil perusahaan,
 *          visi misi, pilar teknologi enterprise, & kemitraan strategis.
 * Output : Halaman terpisah (layer baru) Tentang Kami
 */
require_once __DIR__ . '/../includes/functions.php';

$judulHalaman = 'Tentang Kami';
require __DIR__ . '/../includes/header_public.php';
?>

<!-- Hero Banner Corporate -->
<section class="about-hero" style="position: relative; overflow: hidden; color: #fff; border-radius: var(--radius-lg); padding: 56px 44px; margin: 10px 0 36px; box-shadow: 0 20px 45px -10px rgba(11, 18, 32, 0.4);">
  <img src="../assets/parkir_tentangkami.jpeg" alt="Foto Mobil Kuning Parkir" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; object-position: center; z-index: 1;">
  <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(15, 23, 42, 0.72) 0%, rgba(11, 18, 32, 0.82) 100%); z-index: 2;"></div>
  <div style="position: relative; z-index: 3;">
    <span class="about-badge-corp"><?= icon('logo') ?> PT MRu-Parking Teknologi Indonesia</span>
    <h1>Pionir Sistem Manajemen Parkir Digital &amp; Cerdas Indonesia</h1>
    <p>Kami menghadirkan infrastruktur manajemen parkir berbasis IoT dan Cloud berteknologi enterprise untuk menciptakan pengalaman perparkiran yang aman, cepat, efisien, dan transparan bagi jutaan pengguna.</p>
    <div class="about-hero-actions">
      <a href="tarif.php" class="btn-add pub-cta-lg"><?= icon('tag') ?> Lihat Tarif Resmi</a>
      <a href="peraturan.php" class="pub-cta-ghost" style="color:#fff; border-color:var(--accent);">Peraturan &amp; Tata Tertib →</a>
    </div>
  </div>
</section>

<!-- Section Statistics -->
<div class="about-stats-grid">
  <div class="about-stat-card">
    <div class="num">50+</div>
    <div class="label">Lokasi Terintegrasi</div>
    <div class="desc">Gedung perkantoran, pusat perbelanjaan, &amp; fasilitas umum.</div>
  </div>
  <div class="about-stat-card">
    <div class="num">1.8M+</div>
    <div class="label">Transaksi Parkir</div>
    <div class="desc">Tercatat rapi dan aman dalam basis data terenkripsi.</div>
  </div>
  <div class="about-stat-card">
    <div class="num">99.99%</div>
    <div class="label">System Uptime</div>
    <div class="desc">Keandalan server cloud tanpa kendala operasional.</div>
  </div>
  <div class="about-stat-card">
    <div class="num">24/7</div>
    <div class="label">Dukungan Operasional</div>
    <div class="desc">Tim teknis &amp; staf lapangan bersiap menjaga kelancaran.</div>
  </div>
</div>

<!-- Section Visi & Misi -->
<section class="pub-section">
  <span class="pub-eyebrow">Komitmen Perusahaan</span>
  <h2 class="pub-section-title">Visi &amp; Misi MRu-Parkir</h2>
  
  <div class="vision-mission-grid">
    <div class="vm-card">
      <span class="stat-icon stat-icon-green" style="margin-bottom:14px;"><?= icon('activity') ?></span>
      <h3>Visi Perusahaan</h3>
      <p>Menjadi platform ekosistem manajemen perparkiran pintar (*smart parking platform*) nomor satu di Indonesia yang mengintegrasikan teknologi modern dengan kenyamanan mobilitas masyarakat urban.</p>
    </div>

    <div class="vm-card mission">
      <span class="stat-icon stat-icon-blue" style="margin-bottom:14px;"><?= icon('users') ?></span>
      <h3>Misi Utama</h3>
      <p>Mengembangkan solusi perparkiran pintar yang akurat dan transparan, meminimalkan antrean gerbang parkir, serta menjamin keamanan kendaraan melalui audit trail otomatis real-time.</p>
    </div>
  </div>
</section>

<!-- Section Pilar Teknologi Enterprise -->
<section class="pub-section pub-section-alt" id="teknologi">
  <span class="pub-eyebrow">Inovasi Berkelanjutan</span>
  <h2 class="pub-section-title">Pilar Teknologi MRu-Parkir</h2>
  <p class="pub-note" style="margin-bottom:28px;">Sistem MRu-Parkir dibangun dengan standar teknologi kelas industri untuk menjamin kecepatan dan keandalan data.</p>

  <div class="tech-grid">
    <div class="tech-card">
      <div class="tech-icon stat-icon-blue"><?= icon('dashboard') ?></div>
      <h4>Monitoring Real-Time</h4>
      <p>Dashboard terpadu yang menyajikan data okupansi slot dan transaksi kendaraan detik demi detik.</p>
    </div>

    <div class="tech-card">
      <div class="tech-icon stat-icon-green"><?= icon('car') ?></div>
      <h4>Pencatatan Otomatis</h4>
      <p>Integrasi palang pintu cerdas (*smart gate barrier*) dengan perekaman identitas kendaraan presisi.</p>
    </div>

    <div class="tech-card">
      <div class="tech-icon stat-icon-orange"><?= icon('tag') ?></div>
      <h4>Multi-Tarif Transparan</h4>
      <p>Perhitungan durasi parkir otomatis dibulatkan secara adil dan transparan tanpa biaya siluman.</p>
    </div>

    <div class="tech-card">
      <div class="tech-icon stat-icon-purple"><?= icon('ticket') ?></div>
      <h4>Audit Trail &amp; Keamanan Data</h4>
      <p>Semua aktivitas petugas dan transaksi tercatat pada log terenkripsi demi akuntabilitas operasional.</p>
    </div>
  </div>
</section>

<!-- Section Partnership Call to Action -->
<section class="pub-section" style="padding-bottom: 20px;">
  <div class="card" style="background: linear-gradient(135deg, #0B1220 0%, #132033 100%); color: #ffffff; border: 1px solid rgba(255,255,255,0.1); padding: 36px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
    <div style="display: flex; gap: 24px; align-items: center; justify-content: space-between; flex-wrap: wrap;">
      <div style="max-width: 600px;">
        <span class="pub-eyebrow" style="color: var(--accent);">B2B &amp; Enterprise Partnership</span>
        <h3 style="color: #ffffff; font-size: 22px; margin: 4px 0 10px;">Tertarik Mengintegrasikan MRu-Parkir di Lokasi Anda?</h3>
        <p style="margin: 0; color: #94A3B8; font-size: 14px; line-height: 1.6;">Kami melayani kemitraan pengelolaan gedung, mall, rumah sakit, dan fasilitas publik di seluruh Indonesia dengan skema bagi hasil transparan dan modern.</p>
      </div>
      <a href="index.php#unduh" class="btn-add" style="background: var(--accent); color: #ffffff; font-weight: 700; padding: 12px 24px; font-size: 14.5px;">Hubungi Kemitraan →</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer_public.php'; ?>
