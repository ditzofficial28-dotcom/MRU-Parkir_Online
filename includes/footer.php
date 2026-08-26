    </main>
  </div>
</div>

<script>
  // Sidebar toggle untuk tampilan mobile (transisi diatur lewat CSS)
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const hamburger = document.getElementById('hamburgerBtn');

  function toggleSidebar() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
  }
  hamburger && hamburger.addEventListener('click', toggleSidebar);
  overlay && overlay.addEventListener('click', toggleSidebar);

  // Jam Berjalan Real-Time Indonesia (WIB)
  function updateIndonesianClock() {
    const now = new Date();
    
    // Format Waktu WIB (HH:mm:ss WIB)
    const hours   = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeString = `${hours}:${minutes}:${seconds} WIB`;
    
    // Format Hari & Tanggal Bahasa Indonesia
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    const dayName   = days[now.getDay()];
    const dateNum   = now.getDate();
    const monthName = months[now.getMonth()];
    const yearNum   = now.getFullYear();
    const dateString = `${dayName}, ${dateNum} ${monthName} ${yearNum}`;
    
    document.querySelectorAll('.liveClockTime').forEach(el => el.textContent = timeString);
    document.querySelectorAll('.liveClockDate').forEach(el => el.textContent = dateString);
  }

  updateIndonesianClock();
  setInterval(updateIndonesianClock, 1000);
</script>
</body>
</html>
