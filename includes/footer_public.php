</main>

<footer class="pub-footer">
  <div class="pub-footer-inner">

    <!-- Brand -->
    <div class="pub-footer-brand">
      <img src="../assets/logo_mru.jpg" alt="Logo MRu-Parkir" class="footer-logo-img">
      <div class="pub-footer-brand-text">
        <strong>MRu-Parkir</strong>
        <p>Sistem manajemen parkir terintegrasi — dari pencatatan kendaraan hingga rekap pendapatan, dalam satu platform.</p>
      </div>
    </div>

    <!-- Links -->
    <div class="pub-footer-links">
      <div class="pub-footer-col">
        <span class="pub-footer-col-title">Layanan</span>
        <a href="peraturan.php">Peraturan Parkir</a>
        <a href="tarif.php">Tarif Parkir</a>
        <a href="lacak.php">Lacak Kendaraan</a>
      </div>
      <div class="pub-footer-col">
        <span class="pub-footer-col-title">Perusahaan</span>
        <a href="tentang.php">Tentang Kami</a>
      </div>
    </div>

  </div>
  <div class="pub-footer-bottom">
    © <?= date('Y') ?> PT MRu-Parking Teknologi Indonesia. Seluruh hak cipta dilindungi.
  </div>
</footer>

<script>
  // Theme Toggle Dark / Light
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('mru_public_theme', newTheme);
    });
  }

  // Public Mobile Hamburger Menu Toggle
  const pubHamburgerBtn = document.getElementById('pubHamburgerBtn');
  const pubNav = document.getElementById('pubNav');
  if (pubHamburgerBtn && pubNav) {
    pubHamburgerBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      pubNav.classList.toggle('show');
    });
    document.addEventListener('click', (e) => {
      if (!pubNav.contains(e.target) && !pubHamburgerBtn.contains(e.target)) {
        pubNav.classList.remove('show');
      }
    });
  }
</script>
</body>
</html>
