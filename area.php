<?php
/**
 * Modul  : CRUD Area Parkir (Khusus Role Admin)
 * Input  : nama_area, kapasitas
 * Proses : Simpan / ubah / hapus data area parkir (tb_area_parkir) dengan sinkronisasi slot terisi
 * Output : Form tambah & edit area, tabel daftar area beserta okupansi & tombol aksi
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

// Auto-sync terisi dengan transaksi aktif
$pdo->exec("UPDATE tb_area_parkir a SET terisi = (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')");

$aksi  = $_GET['aksi'] ?? 'list';
$id    = (int) ($_GET['id'] ?? 0);
$pesan = '';
$error = '';

// --- Simpan (Tambah / Edit Area) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = amankanInput($_POST['nama_area'] ?? '');
    $kap     = (int) ($_POST['kapasitas'] ?? 0);
    $id_edit = (int) ($_POST['id_area'] ?? 0);

    if ($nama === '' || $kap <= 0) {
        $error = 'Nama area dan kapasitas (minimal 1) wajib diisi.';
    } else {
        if ($id_edit > 0) {
            // Ambil data terisi saat ini
            $stmtCek = $pdo->prepare("SELECT terisi FROM tb_area_parkir WHERE id_area = ?");
            $stmtCek->execute([$id_edit]);
            $currentArea = $stmtCek->fetch();
            $terisiSaatIni = (int) ($currentArea['terisi'] ?? 0);

            if ($kap < $terisiSaatIni) {
                $error = "Kapasitas baru ($kap) tidak boleh lebih kecil dari kendaraan yang sedang parkir ($terisiSaatIni).";
            } else {
                $stmt = $pdo->prepare("UPDATE tb_area_parkir SET nama_area = ?, kapasitas = ? WHERE id_area = ?");
                $stmt->execute([$nama, $kap, $id_edit]);
                catatLog($pdo, $_SESSION['id_user'], "Mengubah area parkir #$id_edit ($nama, kap: $kap)");
                $pesan = "Area parkir '$nama' berhasil diperbarui.";
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES (?, ?, 0)");
            $stmt->execute([$nama, $kap]);
            $newId = $pdo->lastInsertId();
            catatLog($pdo, $_SESSION['id_user'], "Menambah area parkir baru: $nama (kapasitas: $kap)");
            $pesan = "Area parkir baru '$nama' berhasil ditambahkan.";
        }
    }
}

// --- Hapus Area Parkir ---
if ($aksi === 'hapus' && $id > 0) {
    // Cek apakah ada kendaraan yang sedang parkir di area ini
    $stmtAktif = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_area = ? AND status = 'masuk'");
    $stmtAktif->execute([$id]);
    $adaKendaraan = (int) $stmtAktif->fetchColumn();

    if ($adaKendaraan > 0) {
        $error = "Area parkir tidak dapat dihapus karena masih terdapat $adaKendaraan kendaraan yang sedang parkir aktif.";
    } else {
        try {
            // Hapus riwayat transaksi lama pada area ini jika ada, lalu hapus area
            $pdo->prepare("DELETE FROM tb_transaksi WHERE id_area = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM tb_area_parkir WHERE id_area = ?")->execute([$id]);
            catatLog($pdo, $_SESSION['id_user'], "Menghapus area parkir #$id");
            $pesan = "Area parkir #$id berhasil dihapus.";
        } catch (PDOException $e) {
            $error = 'Gagal menghapus area parkir karena terkait data riwayat transaksi lain.';
        }
    }
}

// Data untuk form edit
$dataEdit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tb_area_parkir WHERE id_area = ?");
    $stmt->execute([$id]);
    $dataEdit = $stmt->fetch();
}

$daftarArea = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY id_area ASC")->fetchAll();

$judulHalaman = 'CRUD Area Parkir';
require __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
  <div>
    <a href="dashboard.php" style="text-decoration:none; color:var(--ink-muted); font-size:13px; font-weight:600;">&larr; Kembali ke Dashboard Admin</a>
    <h2 style="margin:4px 0 0; font-size:22px;"><?= $dataEdit ? 'Edit Area Parkir #' . $dataEdit['id_area'] : 'Manajemen Floor &amp; Area Parkir' ?></h2>
  </div>
  <a href="../public/lacak.php" target="_blank" class="btn-add" style="background:var(--surface); color:var(--ink); border:1px solid var(--border); font-size:13px; font-weight:600; text-decoration:none;">
    <?= icon('map') ?> Lacak Publik ↗
  </a>
</div>

<?php if ($pesan): ?>
  <div style="background:var(--success-soft); color:var(--success); border:1px solid rgba(21,128,61,0.2); padding:12px 16px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600;">
    ✓ <?= htmlspecialchars($pesan) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:var(--danger-soft); color:var(--danger); border:1px solid rgba(185,28,28,0.2); padding:12px 16px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600;">
    ✕ <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- Form Tambah / Edit Area Parkir -->
<div class="card" style="margin-bottom:28px;">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px;"><?= $dataEdit ? 'Ubah Area: ' . htmlspecialchars($dataEdit['nama_area']) : '+ Tambah Area Parkir Baru' ?></h3>
  
  <form method="post" action="area.php" style="display:grid; grid-template-columns: 1.5fr 1fr auto; gap:14px; align-items:end;">
    <input type="hidden" name="id_area" value="<?= $dataEdit['id_area'] ?? 0 ?>">
    
    <div>
      <label style="margin-top:0;">Nama Area / Lantai Parkir</label>
      <input type="text" name="nama_area" value="<?= htmlspecialchars($dataEdit['nama_area'] ?? '') ?>" placeholder="mis. Area A - Motor Lantai 1" required>
    </div>
    
    <div>
      <label style="margin-top:0;">Kapasitas Maksimal Slot</label>
      <input type="number" name="kapasitas" min="1" value="<?= $dataEdit['kapasitas'] ?? '' ?>" placeholder="mis. 50" required>
    </div>

    <div>
      <button type="submit" class="btn-add" style="height:42px; width:100%; justify-content:center; margin-top:0;">
        <?= $dataEdit ? 'Simpan Perubahan Area' : '+ Tambah Area Baru' ?>
      </button>
    </div>
  </form>
</div>

<!-- Tabel Daftar Area Parkir -->
<div class="card">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px;">Daftar Area Lokasi Parkir (<?= count($daftarArea) ?> Area)</h3>
  
  <table style="width:100%;">
    <thead>
      <tr>
        <th>ID Area</th>
        <th>Nama Area Lokasi</th>
        <th>Kapasitas Slot</th>
        <th>Slot Terisi</th>
        <th>Sisa Kosong</th>
        <th>Status Okupansi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($daftarArea as $a): 
        $sisa = (int)$a['kapasitas'] - (int)$a['terisi'];
        $pct = $a['kapasitas'] > 0 ? min(100, round($a['terisi'] / $a['kapasitas'] * 100)) : 0;
      ?>
        <tr>
          <td>#<?= $a['id_area'] ?></td>
          <td><strong><?= htmlspecialchars($a['nama_area']) ?></strong></td>
          <td><strong><?= $a['kapasitas'] ?></strong> slot</td>
          <td><strong style="color:var(--accent-dark);"><?= $a['terisi'] ?></strong> terisi</td>
          <td><strong><?= $sisa ?></strong> slot tersedia</td>
          <td>
            <div style="display:flex; align-items:center; gap:8px;">
              <div class="occupancy-bar" style="width:80px; height:8px;">
                <div class="occupancy-fill <?= $pct >= 90 ? 'fill-danger' : ($pct >= 60 ? 'fill-warning' : 'fill-success') ?>" style="width:<?= $pct ?>%"></div>
              </div>
              <span class="badge <?= $sisa <= 0 ? 'badge-danger' : ($pct >= 60 ? 'badge-warning' : 'badge-success') ?>">
                <?= $pct ?>%
              </span>
            </div>
          </td>
          <td style="white-space:nowrap;">
            <a class="btn btn-edit" href="area.php?aksi=edit&id=<?= $a['id_area'] ?>">Edit</a>
            <a class="btn btn-del" href="area.php?aksi=hapus&id=<?= $a['id_area'] ?>" onclick="return confirm('Hapus area parkir <?= htmlspecialchars($a['nama_area']) ?>?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$daftarArea): ?>
        <tr><td colspan="7" style="text-align:center; color:var(--ink-muted); padding:24px;">Belum ada area parkir yang dibuat.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
