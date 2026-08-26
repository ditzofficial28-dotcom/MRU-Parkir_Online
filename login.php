<?php
/**
 * File   : login.php
 * Modul  : Proses Login Utama MRu-Parkir
 * Input  : username, password (POST)
 * Proses : Validasi kredensial terhadap tb_user, verifikasi password_hash,
 *          catat log aktivitas, set session role & redirect ke dashboard sesuai role.
 * Output : Redirect ke admin/dashboard.php, petugas/dashboard.php, atau owner/dashboard.php
 */
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';

// Jika sudah login, langsung redirect ke dashboard admin
if (isset($_SESSION['id_user'])) {
    header('Location: admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = amankanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tb_user WHERE username = ? AND status_aktif = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama_lengkap'];
            $_SESSION['role']    = $user['role'];

            catatLog($pdo, (int) $user['id_user'], 'Login ke sistem');

            header('Location: admin/dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk Sistem · MRu-Parkir</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
<style>
.login-page {
  display: grid;
  grid-template-columns: 1.1fr 1fr;
  min-height: 100vh;
  background: #0B1220;
}
@media (max-width: 860px) {
  .login-page { grid-template-columns: 1fr; }
  .login-brand { display: none; }
}
.login-brand {
  background: radial-gradient(circle at 10% 20%, #1e293b 0%, #0B1220 70%);
  padding: 60px 48px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  color: #fff;
  border-right: 1px solid rgba(255,255,255,0.08);
}
.login-brand .brand-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 32px;
}
.login-brand .brand-logo-img {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  object-fit: cover;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
}
.login-brand h1 {
  font-family: 'Sora', sans-serif;
  font-size: 34px;
  line-height: 1.25;
  color: #fff;
  margin-bottom: 16px;
}
.login-brand p {
  font-size: 15px;
  color: #94A3B8;
  line-height: 1.6;
  max-width: 480px;
  margin-bottom: 36px;
}
.stat-row {
  display: flex;
  gap: 32px;
  padding-top: 24px;
  border-top: 1px solid rgba(255,255,255,0.1);
}
.stat strong {
  display: block;
  font-family: 'Sora', sans-serif;
  font-size: 24px;
  color: #10B981;
}
.stat span {
  font-size: 12px;
  color: #94A3B8;
}

.login-form-side {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 28px;
  background: #F8FAFC;
}
.login-box {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  padding: 36px 32px;
  border-radius: 18px;
  box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.12);
  border: 1px solid #E2E8F0;
}
.login-box h2 {
  font-size: 24px;
  margin-bottom: 6px;
}
.login-box .subtitle {
  font-size: 13.5px;
  color: #64748B;
  margin-bottom: 24px;
}
.login-box label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: #475569;
  margin: 14px 0 6px;
}
.login-box input {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid #CBD5E1;
  border-radius: 10px;
  font-size: 14px;
  transition: border-color .2s, box-shadow .2s;
  box-sizing: border-box;
}
.login-box input:focus {
  outline: none;
  border-color: #10B981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.18);
}
.login-box button {
  width: 100%;
  padding: 12px;
  background: #10B981;
  color: #ffffff;
  font-weight: 700;
  font-size: 14.5px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  margin-top: 22px;
  transition: background .2s, transform .15s;
}
.login-box button:hover {
  background: #0D9268;
  transform: translateY(-1px);
}
.alert-error {
  background: #FEE2E2;
  color: #B91C1C;
  border: 1px solid rgba(185, 28, 28, 0.2);
  padding: 12px 14px;
  border-radius: 8px;
  font-size: 13px;
  margin-bottom: 16px;
}
.hint {
  font-size: 11.5px;
  color: #64748B;
  margin-top: 20px;
  text-align: center;
  background: #F1F5F9;
  padding: 10px;
  border-radius: 8px;
}
</style>
</head>
<body>
<div class="login-page">

  <!-- Left Side Branding -->
  <div class="login-brand">
    <div class="brand-header">
      <img src="assets/logo_mru.jpg" alt="Logo MRu-Parkir" class="brand-logo-img">
      <strong style="font-family:'Sora',sans-serif; font-size:20px;">MRu-Parkir</strong>
    </div>
    <h1>Kelola operasional parkir Anda dalam satu platform terpadu.</h1>
    <p>MRu-Parkir membantu mencatat kendaraan masuk-keluar, menghitung tarif otomatis, dan menyajikan laporan kinerja serta audit trail secara real-time dalam satu Dashboard Administrator terpadu.</p>
    <div class="stat-row">
      <div class="stat"><strong>Master Admin</strong><span>Panel Terpadu</span></div>
      <div class="stat"><strong>Real-Time</strong><span>Pencatatan Transaksi</span></div>
      <div class="stat"><strong>Audit Trail</strong><span>Keamanan Log Sistem</span></div>
    </div>
  </div>

  <!-- Right Side Form -->
  <div class="login-form-side">
    <div class="login-box">
      <a href="public/index.php" style="display:inline-flex; align-items:center; gap:6px; font-size:12.5px; color:#64748B; text-decoration:none; margin-bottom:20px; font-weight:600;">← Beranda Publik</a>
      <h2>Selamat Datang</h2>
      <p class="subtitle">Masuk untuk melanjutkan ke Dashboard Administrator.</p>

      <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="login.php">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" required autofocus placeholder="mis. admin">
        
        <label for="password">Password</label>
        <input type="password" name="password" id="password" required placeholder="••••••••">
        
        <button type="submit">Masuk ke Dashboard →</button>
      </form>

      <p class="hint">💡 <strong>Akun Pengujian Administrator:</strong><br>Username: <code>admin</code> | Password: <code>password123</code></p>
    </div>
  </div>

</div>
</body>
</html>
