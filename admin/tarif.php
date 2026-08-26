<?php
/**
 * Modul  : CRUD Tarif Parkir (khusus role admin)
 * Input  : jenis_kendaraan, tarif_per_jam
 * Proses : simpan/ubah/hapus baris tb_tarif
 * Output : tabel daftar tarif
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

$aksi = $_GET['aksi'] ?? 'list';
$id   = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = amankanInput($_POST['jenis_kendaraan']);
    $tarif = (int) $_POST['tarif_per_jam'];
    $id_edit = $_POST['id_tarif'] ?? null;

    if ($id_edit) {
        $stmt = $pdo->prepare("UPDATE tb_tarif SET jenis_kendaraan=?, tarif_per_jam=? WHERE id_tarif=?");
        $stmt->execute([$jenis, $tarif, $id_edit]);
        catatLog($pdo, $_SESSION['id_user'], "Mengubah tarif #$id_edit");
    } else {
        $stmt = $pdo->prepare("INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES (?,?)");
        $stmt->execute([$jenis, $tarif]);
        catatLog($pdo, $_SESSION['id_user'], "Menambah tarif baru: $jenis");
    }
    header('Location: tarif.php');
    exit;
}

if ($aksi === 'hapus' && $id) {
    $pdo->prepare("DELETE FROM tb_tarif WHERE id_tarif = ?")->execute([$id]);
    catatLog($pdo, $_SESSION['id_user'], "Menghapus tarif #$id");
    header('Location: tarif.php');
    exit;
}

$dataEdit = null;
if ($aksi === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM tb_tarif WHERE id_tarif = ?");
    $stmt->execute([$id]);
    $dataEdit = $stmt->fetch();
}

$daftar = $pdo->query("SELECT * FROM tb_tarif ORDER BY id_tarif")->fetchAll();
$judulHalaman = 'CRUD Tarif Parkir';
require __DIR__ . '/../includes/header.php';
?>
<a href="parkir.php">&larr; Manajemen Parkir</a>
<h3><?= $dataEdit ? 'Edit Tarif' : 'Tambah Tarif' ?></h3>
<form method="post" class="card">
  <input type="hidden" name="id_tarif" value="<?= $dataEdit['id_tarif'] ?? '' ?>">
  <label>Jenis Kendaraan</label>
  <select name="jenis_kendaraan" required>
    <?php foreach (['motor','mobil','lainnya'] as $j): ?>
      <option value="<?= $j ?>" <?= (($dataEdit['jenis_kendaraan'] ?? '') === $j) ? 'selected' : '' ?>><?= $j ?></option>
    <?php endforeach; ?>
  </select><br><br>
  <label>Tarif per Jam (Rp)</label>
  <input type="number" name="tarif_per_jam" value="<?= $dataEdit['tarif_per_jam'] ?? '' ?>" required><br><br>
  <button type="submit">Simpan</button>
</form>

<h3>Daftar Tarif</h3>
<table>
<tr><th>#</th><th>Jenis</th><th>Tarif/Jam</th><th>Aksi</th></tr>
<?php foreach ($daftar as $t): ?>
<tr>
  <td><?= $t['id_tarif'] ?></td>
  <td><?= htmlspecialchars($t['jenis_kendaraan']) ?></td>
  <td><?= formatRupiah($t['tarif_per_jam']) ?></td>
  <td>
    <a class="btn btn-edit" href="tarif.php?aksi=edit&id=<?= $t['id_tarif'] ?>">Edit</a>
    <a class="btn btn-del" href="tarif.php?aksi=hapus&id=<?= $t['id_tarif'] ?>" onclick="return confirm('Hapus tarif ini?')">Hapus</a>
  </td>
</tr>
<?php endforeach; ?>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
