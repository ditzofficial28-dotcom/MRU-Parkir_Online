<?php
/**
 * Modul  : CRUD User (Khusus Role Admin)
 * Input  : nama_lengkap, username, password, role, status_aktif
 * Proses : Simpan/ubah/hapus data pengguna sistem (tb_user) dengan penanganan relasi aman
 * Output : Form tambah/edit user, tabel pengguna, dan pesan notifikasi
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

$aksi  = $_GET['aksi'] ?? 'list';
$id    = (int) ($_GET['id'] ?? 0);
$pesan = '';
$error = '';

// --- Simpan (Tambah / Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = amankanInput($_POST['nama_lengkap'] ?? '');
    $username = strtolower(trim(amankanInput($_POST['username'] ?? '')));
    $role     = 'admin';
    $status   = isset($_POST['status_aktif']) ? 1 : 0;
    $id_edit  = (int) ($_POST['id_user'] ?? 0);

    if ($nama === '' || $username === '') {
        $error = 'Nama lengkap dan username wajib diisi.';
    } else {
        if ($id_edit > 0) {
            // Check username uniqueness (except current user)
            $stmtCek = $pdo->prepare("SELECT id_user FROM tb_user WHERE username = ? AND id_user != ? LIMIT 1");
            $stmtCek->execute([$username, $id_edit]);
            if ($stmtCek->fetch()) {
                $error = "Username '$username' sudah digunakan pengguna lain.";
            } else {
                if (!empty($_POST['password'])) {
                    $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE tb_user SET nama_lengkap=?, username=?, password=?, role=?, status_aktif=? WHERE id_user=?");
                    $stmt->execute([$nama, $username, $hash, $role, $status, $id_edit]);
                } else {
                    $stmt = $pdo->prepare("UPDATE tb_user SET nama_lengkap=?, username=?, role=?, status_aktif=? WHERE id_user=?");
                    $stmt->execute([$nama, $username, $role, $status, $id_edit]);
                }
                catatLog($pdo, $_SESSION['id_user'], "Mengubah data user #$id_edit ($username)");
                $pesan = "Pengguna $username berhasil diperbarui.";
            }
        } else {
            // Check username uniqueness
            $stmtCek = $pdo->prepare("SELECT id_user FROM tb_user WHERE username = ? LIMIT 1");
            $stmtCek->execute([$username]);
            if ($stmtCek->fetch()) {
                $error = "Username '$username' sudah terdaftar.";
            } else if (empty($_POST['password'])) {
                $error = 'Password wajib diisi untuk pengguna baru.';
            } else {
                $hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES (?,?,?,?,?)");
                $stmt->execute([$nama, $username, $hash, $role, $status]);
                $newId = $pdo->lastInsertId();
                catatLog($pdo, $_SESSION['id_user'], "Menambah user admin baru: $username");
                $pesan = "Pengguna baru $username berhasil ditambahkan.";
            }
        }
    }
}

// --- Hapus / Nonaktifkan User ---
if ($aksi === 'hapus' && $id > 0) {
    if ($id === (int) $_SESSION['id_user']) {
        $error = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan.';
    } else {
        try {
            // Cek apakah user memiliki riwayat transaksi/log/kendaraan
            $hasTx = $pdo->prepare("SELECT COUNT(*) FROM tb_transaksi WHERE id_user = ?");
            $hasTx->execute([$id]);
            $cntTx = (int) $hasTx->fetchColumn();

            $hasLog = $pdo->prepare("SELECT COUNT(*) FROM tb_log_aktivitas WHERE id_user = ?");
            $hasLog->execute([$id]);
            $cntLog = (int) $hasLog->fetchColumn();

            if ($cntTx > 0 || $cntLog > 0) {
                // Soft delete / nonaktifkan agar audit trail transaksi tidak rusak
                $stmt = $pdo->prepare("UPDATE tb_user SET status_aktif = 0 WHERE id_user = ?");
                $stmt->execute([$id]);
                catatLog($pdo, $_SESSION['id_user'], "Mendonaktifkan akun user #$id karena memiliki riwayat data");
                $pesan = "Pengguna #$id memiliki riwayat transaksi, status diubah menjadi Nonaktif (soft-delete).";
            } else {
                // Hard delete jika tidak ada relasi
                $stmt = $pdo->prepare("DELETE FROM tb_user WHERE id_user = ?");
                $stmt->execute([$id]);
                catatLog($pdo, $_SESSION['id_user'], "Menghapus akun user #$id secara permanen");
                $pesan = "Pengguna #$id berhasil dihapus secara permanen.";
            }
        } catch (PDOException $e) {
            // Fallback soft delete jika FK constraint menolak
            $pdo->prepare("UPDATE tb_user SET status_aktif = 0 WHERE id_user = ?")->execute([$id]);
            $pesan = "Status pengguna #$id berhasil diubah menjadi Nonaktif.";
        }
    }
}

// Fetch Edit Data
$dataEdit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmtEdit = $pdo->prepare("SELECT * FROM tb_user WHERE id_user = ? LIMIT 1");
    $stmtEdit->execute([$id]);
    $dataEdit = $stmtEdit->fetch();
}

$daftarUser = $pdo->query("SELECT * FROM tb_user ORDER BY id_user ASC")->fetchAll();

$judulHalaman = 'Manajemen Pengguna';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($pesan): ?>
  <div class="alert alert-success" style="padding:12px 16px; background:var(--success-soft); color:var(--success); border-radius:var(--radius-sm); margin-bottom:20px;">
    <?= htmlspecialchars($pesan) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger" style="padding:12px 16px; background:var(--danger-soft); color:var(--danger); border-radius:var(--radius-sm); margin-bottom:20px;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- Form Tambah / Edit User -->
<div class="card" style="margin-bottom:28px;">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px;"><?= $dataEdit ? 'Ubah Pengguna: ' . htmlspecialchars($dataEdit['username']) : '+ Tambah Pengguna Admin Baru' ?></h3>
  
  <form method="post" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap:14px; align-items:end;">
    <input type="hidden" name="id_user" value="<?= $dataEdit['id_user'] ?? 0 ?>">
    <input type="hidden" name="role" value="admin">
    
    <div>
      <label style="margin-top:0;">Nama Lengkap</label>
      <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($dataEdit['nama_lengkap'] ?? '') ?>" placeholder="mis. Budi Santoso" required>
    </div>
    
    <div>
      <label style="margin-top:0;">Username</label>
      <input type="text" name="username" value="<?= htmlspecialchars($dataEdit['username'] ?? '') ?>" placeholder="mis. admin2" required>
    </div>

    <div>
      <label style="margin-top:0;">Password <?= $dataEdit ? '<small style="color:var(--ink-muted);">(Kosongkan jika tidak diubah)</small>' : '' ?></label>
      <input type="password" name="password" placeholder="••••••••" <?= $dataEdit ? '' : 'required' ?>>
    </div>

    <div>
      <label style="margin-top:0; display:flex; align-items:center; gap:6px; cursor:pointer; height:42px;">
        <input type="checkbox" name="status_aktif" value="1" <?= (!$dataEdit || !empty($dataEdit['status_aktif'])) ? 'checked' : '' ?>>
        <span>Status Akun Aktif</span>
      </label>
    </div>

    <div>
      <button type="submit" class="btn-add" style="height:42px; width:100%; justify-content:center; margin-top:0;">
        <?= $dataEdit ? 'Simpan Perubahan' : '+ Tambah User Admin' ?>
      </button>
    </div>
  </form>
</div>

<!-- Tabel Daftar User -->
<div class="card">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px;">Daftar Pengguna Sistem (<?= count($daftarUser) ?> Akun)</h3>
  
  <table style="width:100%;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nama Lengkap</th>
        <th>Username</th>
        <th>Role Akses</th>
        <th>Status Akun</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($daftarUser as $u): ?>
        <tr>
          <td>#<?= $u['id_user'] ?></td>
          <td><strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong></td>
          <td><code style="font-family:var(--font-mono); font-size:13px; color:var(--accent-dark);"><?= htmlspecialchars($u['username']) ?></code></td>
          <td>
            <span class="badge <?= $u['role'] === 'admin' ? 'badge-info' : ($u['role'] === 'owner' ? 'badge-warning' : 'badge-success') ?>">
              <?= ucfirst($u['role']) ?>
            </span>
          </td>
          <td>
            <span class="badge <?= $u['status_aktif'] ? 'badge-success' : 'badge-danger' ?>">
              <?= $u['status_aktif'] ? '● Aktif' : '○ Nonaktif' ?>
            </span>
          </td>
          <td style="white-space:nowrap;">
            <a class="btn btn-edit" href="user.php?aksi=edit&id=<?= $u['id_user'] ?>">Edit</a>
            <?php if ($u['id_user'] != $_SESSION['id_user']): ?>
              <a class="btn btn-del" href="user.php?aksi=hapus&id=<?= $u['id_user'] ?>" onclick="return confirm('Hapus / Nonaktifkan pengguna <?= htmlspecialchars($u['username']) ?>?')">Hapus</a>
            <?php else: ?>
              <span style="font-size:11px; color:var(--ink-muted); italic;">(Akun Anda)</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
