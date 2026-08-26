<?php
/**
 * Modul  : Akses Log Aktivitas (khusus role admin)
 * Input  : - (opsional filter tanggal via GET)
 * Proses : SELECT tb_log_aktivitas JOIN tb_user, dengan LIMIT agar ringan
 * Output : tabel riwayat aktivitas seluruh user
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

$stmt = $pdo->query(
    "SELECT l.id_log, u.nama_lengkap, u.role, l.aktivitas, l.waktu_aktivitas
     FROM tb_log_aktivitas l
     JOIN tb_user u ON u.id_user = l.id_user
     ORDER BY l.waktu_aktivitas DESC
     LIMIT 200"
);
$logs = $stmt->fetchAll();

$judulHalaman = 'Log Aktivitas';
require __DIR__ . '/../includes/header.php';
?>
<a href="dashboard.php">&larr; Dashboard</a>
<h3>Log Aktivitas (200 terbaru)</h3>
<table>
<tr><th>#</th><th>Nama</th><th>Role</th><th>Aktivitas</th><th>Waktu</th></tr>
<?php foreach ($logs as $l): ?>
<tr>
  <td><?= $l['id_log'] ?></td>
  <td><?= htmlspecialchars($l['nama_lengkap']) ?></td>
  <td><?= htmlspecialchars($l['role']) ?></td>
  <td><?= htmlspecialchars($l['aktivitas']) ?></td>
  <td><?= htmlspecialchars($l['waktu_aktivitas']) ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
