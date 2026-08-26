<?php
/**
 * Modul  : Peraturan & Tata Tertib Parkir (Publik)
 * Proses : Menampilkan pedoman keselamatan, keamanan, dan aturan resmi parkir
 * Output : Halaman terpisah (layer baru) Peraturan MRu-Parking
 */
require_once __DIR__ . '/../includes/functions.php';

$judulHalaman = 'Peraturan Parkir';
require __DIR__ . '/../includes/header_public.php';
?>

<section class="pub-section">
  <span class="pub-eyebrow">Keselamatan &amp; Ketertiban</span>
  <h2 class="pub-section-title">Peraturan &amp; Tata Tertib Parkir</h2>
  <p class="pub-note" style="margin-bottom:32px;">Pedoman resmi keselamatan, keamanan, dan kenyamanan bagi seluruh pengguna area parkir MRu-Parking.</p>

  <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px; margin-bottom: 36px;">
    <div class="feature-card" style="border-top: 4px solid var(--info);">
      <span class="stat-icon stat-icon-blue"><?= icon('activity') ?></span>
      <h3>1. Batas Kecepatan 10 km/jam</h3>
      <p>Seluruh pengemudi wajib menjaga kecepatan maksimal 10 km/jam di dalam area parkir. Dahulukan pejalan kaki dan ikuti petunjuk rambu serta panah arah jalan.</p>
    </div>

    <div class="feature-card" style="border-top: 4px solid var(--accent);">
      <span class="stat-icon stat-icon-green"><?= icon('car') ?></span>
      <h3>2. Parkir Tepat Pada Marka</h3>
      <p>Parkirkan kendaraan dengan posisi lurus di dalam batas garis marka yang ditentukan. Dilarang memarkir di area jalur evakuasi, pintu darurat, atau menghalangi kendaraan lain.</p>
    </div>

    <div class="feature-card" style="border-top: 4px solid var(--brand-orange);">
      <span class="stat-icon stat-icon-orange"><?= icon('tag') ?></span>
      <h3>3. Keamanan &amp; Barang Berharga</h3>
      <p>Matikan mesin, tarik rem tangan, dan kunci ganda kendaraan Anda. Jangan pernah meninggalkan barang berharga (laptop, uang, perhiasan) atau hewan di dalam kendaraan.</p>
    </div>

    <div class="feature-card" style="border-top: 4px solid var(--brand-purple);">
      <span class="stat-icon stat-icon-purple"><?= icon('ticket') ?></span>
      <h3>4. Bukti Struk &amp; Pembayaran</h3>
      <p>Simpan struk atau bukti parkir Anda hingga keluar area. Pembayaran dilakukan sesuai durasi jam parkir dan tarif resmi yang berlaku pada tiap jenis kendaraan.</p>
    </div>
  </div>

  <div class="card" style="background: linear-gradient(135deg, #0B4A6F 0%, #0F9D6D 100%); color: #ffffff; border: none; padding: 28px; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
    <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
      <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
        <?= icon('users') ?>
      </div>
      <div style="flex: 1; min-width: 260px;">
        <h3 style="color: #ffffff; font-size: 18px; margin: 0 0 6px;">Butuh Bantuan Petugas Parkir?</h3>
        <p style="margin: 0; opacity: 0.9; font-size: 13.5px;">Jika Anda mengalami kendala saat parkir, karcis hilang, atau membutuhkan bantuan darurat, segera hubungi petugas pos parkir terdekat.</p>
      </div>
      <a href="lacak.php" class="btn-add" style="background: #ffffff; color: var(--ink); font-weight: 700; padding: 10px 20px;">Lacak Kendaraan →</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer_public.php'; ?>
