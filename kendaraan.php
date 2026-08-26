<?php
/**
 * Modul  : CRUD Kendaraan (Khusus Admin)
 * Input  : plat_nomor, jenis_kendaraan, warna, pemilik, id_area (opsional catat masuk)
 * Proses : Simpan/ubah/hapus baris tb_kendaraan & otomatis daftarkan ke area parkir jika dipilih
 * Output : Tabel daftar kendaraan + pilihan area lokasi parkir & link verifikasi lacak publik
 */
require_once __DIR__ . '/../includes/auth.php';
cekRole(['admin']);

// Auto-sync terisi dengan transaksi aktif
$pdo->exec("UPDATE tb_area_parkir a SET terisi = (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')");

$aksi  = $_GET['aksi'] ?? 'list';
$id    = (int) ($_GET['id'] ?? 0);
$q     = amankanInput($_GET['q'] ?? '');
$pesan = '';
$error = '';

$areaList = $pdo->query("SELECT * FROM tb_area_parkir ORDER BY nama_area")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plat    = strtoupper(trim(amankanInput($_POST['plat_nomor'] ?? '')));
    $jenis   = amankanInput($_POST['jenis_kendaraan'] ?? 'mobil');
    $warna   = amankanInput($_POST['warna'] ?? '');
    $id_area = (int) ($_POST['id_area'] ?? 0);
    $id_edit = (int) ($_POST['id_kendaraan'] ?? 0);

    if ($plat === '') {
        $error = 'Plat nomor kendaraan wajib diisi.';
    } else {
        if ($id_edit > 0) {
            $stmt = $pdo->prepare("UPDATE tb_kendaraan SET plat_nomor=?, jenis_kendaraan=?, warna=? WHERE id_kendaraan=?");
            $stmt->execute([$plat, $jenis, $warna, $id_edit]);
            catatLog($pdo, $_SESSION['id_user'], "Mengubah data kendaraan #$id_edit ($plat)");
            $pesan = "Data kendaraan $plat berhasil diperbarui.";
        } else {
            // Cek jika plat sudah terdaftar
            $stmtCek = $pdo->prepare("SELECT id_kendaraan FROM tb_kendaraan WHERE plat_nomor = ? LIMIT 1");
            $stmtCek->execute([$plat]);
            $existing = $stmtCek->fetch();

            if ($existing) {
                $id_kendaraan = $existing['id_kendaraan'];
                $pdo->prepare("UPDATE tb_kendaraan SET jenis_kendaraan=?, warna=COALESCE(NULLIF(?,''), warna) WHERE id_kendaraan=?")
                    ->execute([$jenis, $warna, $id_kendaraan]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, id_user) VALUES (?,?,?,?)");
                $stmt->execute([$plat, $jenis, $warna, $_SESSION['id_user']]);
                $id_kendaraan = $pdo->lastInsertId();
            }

            // Jika area lokasi parkir dipilih -> otomatis buat transaksi parkir masuk!
            if ($id_area > 0) {
                if (!cekKapasitasArea($pdo, $id_area)) {
                    $error = 'Area parkir yang dipilih sudah penuh, kendaraan hanya disimpan ke data master.';
                } else {
                    // Cek apakah kendaraan ini sudah sedang parkir di mana saja
                    $stmtCekParkir = $pdo->prepare("SELECT id_parkir FROM tb_transaksi WHERE id_kendaraan = ? AND status = 'masuk' LIMIT 1");
                    $stmtCekParkir->execute([$id_kendaraan]);
                    if ($stmtCekParkir->fetch()) {
                        $error = "Kendaraan $plat sudah tercatat sedang parkir saat ini.";
                    } else {
                        $tarif = ambilTarifByJenis($pdo, $jenis);
                        if (!$tarif) {
                            $error = "Tarif untuk jenis '$jenis' belum diatur.";
                        } else {
                            $stmtTx = $pdo->prepare(
                                "INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, id_tarif, status, id_user, id_area)
                                 VALUES (?, NOW(), ?, 'masuk', ?, ?)"
                            );
                            $stmtTx->execute([$id_kendaraan, $tarif['id_tarif'], $_SESSION['id_user'], $id_area]);
                            ubahTerisiArea($pdo, $id_area, +1);
                            catatLog($pdo, $_SESSION['id_user'], "Menambah kendaraan $plat & mencatat masuk ke area #$id_area");
                            $pesan = "Kendaraan $plat berhasil ditambahkan & langsung terdaftar parkir aktif!";
                        }
                    }
                }
            } else {
                catatLog($pdo, $_SESSION['id_user'], "Menambah kendaraan master baru: $plat");
                $pesan = "Kendaraan $plat berhasil disimpan ke data master.";
            }
        }
    }
}

// Proses Transaksi Keluar Kendaraan & Calculate Revenue
if ($aksi === 'keluar' && $id > 0) {
    $stmtTx = $pdo->prepare(
        "SELECT t.*, k.plat_nomor, k.jenis_kendaraan, tr.tarif_per_jam, a.id_area, a.nama_area
         FROM tb_transaksi t
         JOIN tb_kendaraan k ON k.id_kendaraan = t.id_kendaraan
         JOIN tb_tarif tr ON tr.id_tarif = t.id_tarif
         JOIN tb_area_parkir a ON a.id_area = t.id_area
         WHERE t.id_kendaraan = ? AND t.status = 'masuk' ORDER BY t.waktu_masuk DESC LIMIT 1"
    );
    $stmtTx->execute([$id]);
    $tx = $stmtTx->fetch();

    if ($tx) {
        $waktuKeluar = date('Y-m-d H:i:s');
        $durasiJam   = hitungDurasiJam($tx['waktu_masuk'], $waktuKeluar);
        $biayaTotal  = hitungBiaya($durasiJam, (float)$tx['tarif_per_jam']);

        $stmtOut = $pdo->prepare(
            "UPDATE tb_transaksi 
             SET waktu_keluar = ?, durasi_jam = ?, biaya_total = ?, status = 'keluar' 
             WHERE id_parkir = ?"
        );
        $stmtOut->execute([$waktuKeluar, $durasiJam, $biayaTotal, $tx['id_parkir']]);

        ubahTerisiArea($pdo, (int)$tx['id_area'], -1);
        catatLog($pdo, $_SESSION['id_user'], "Proses KELUAR kendaraan {$tx['plat_nomor']} di {$tx['nama_area']} - Durasi: {$durasiJam} jam - Pendapatan: Rp " . number_format($biayaTotal, 0, ',', '.'));

        $biayaFmt = formatRupiah($biayaTotal);
        $pesan = "Kendaraan <strong>{$tx['plat_nomor']}</strong> berhasil diproses KELUAR! Total Biaya (Pendapatan): <strong style='font-size:15px;'>{$biayaFmt}</strong> ({$durasiJam} jam). <a href='struk.php?id={$tx['id_parkir']}' target='_blank' style='background:#10B981; color:#ffffff; padding:5px 14px; border-radius:6px; text-decoration:none; font-weight:700; font-size:12px; margin-left:10px; display:inline-flex; align-items:center; gap:4px; box-shadow:0 2px 5px rgba(0,0,0,0.15);'>🖨️ Cetak Struk / E-Karcis ↗</a>";
    } else {
        $error = "Kendaraan ini sedang tidak parkir aktif.";
    }
}

// Proses Hapus Kendaraan + Sinkronisasi Slot Area Parkir
if ($aksi === 'hapus' && $id > 0) {
    // Ambil transaksi aktif yang terkait kendaraan ini
    $stmtAktif = $pdo->prepare("SELECT id_area FROM tb_transaksi WHERE id_kendaraan = ? AND status = 'masuk'");
    $stmtAktif->execute([$id]);
    $transaksiAktif = $stmtAktif->fetchAll();

    foreach ($transaksiAktif as $t) {
        ubahTerisiArea($pdo, (int) $t['id_area'], -1);
    }

    $pdo->prepare("DELETE FROM tb_transaksi WHERE id_kendaraan = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM tb_kendaraan WHERE id_kendaraan = ?")->execute([$id]);

    // Resync terisi pada area parkir
    $pdo->exec("UPDATE tb_area_parkir a SET terisi = (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk')");

    catatLog($pdo, $_SESSION['id_user'], "Menghapus kendaraan #$id beserta transaksi terkait");
    header('Location: kendaraan.php?pesan=dihapus');
    exit;
}

$dataEdit = null;
if ($aksi === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tb_kendaraan WHERE id_kendaraan = ?");
    $stmt->execute([$id]);
    $dataEdit = $stmt->fetch();
}

// Query pencarian kendaraan beserta status parkirnya
if ($q !== '') {
    $stmtDaftar = $pdo->prepare(
        "SELECT k.*, t.id_parkir, t.status AS status_parkir, t.waktu_masuk, a.nama_area
         FROM tb_kendaraan k
         LEFT JOIN tb_transaksi t ON t.id_kendaraan = k.id_kendaraan AND t.status = 'masuk'
         LEFT JOIN tb_area_parkir a ON a.id_area = t.id_area
         WHERE k.plat_nomor LIKE ? OR k.warna LIKE ?
         ORDER BY k.id_kendaraan DESC LIMIT 100"
    );
    $stmtDaftar->execute(["%$q%", "%$q%"]);
    $daftar = $stmtDaftar->fetchAll();
} else {
    $daftar = $pdo->query(
        "SELECT k.*, t.id_parkir, t.status AS status_parkir, t.waktu_masuk, a.nama_area
         FROM tb_kendaraan k
         LEFT JOIN tb_transaksi t ON t.id_kendaraan = k.id_kendaraan AND t.status = 'masuk'
         LEFT JOIN tb_area_parkir a ON a.id_area = t.id_area
         ORDER BY k.id_kendaraan DESC LIMIT 100"
    )->fetchAll();
}

$judulHalaman = 'CRUD Kendaraan & Lokasi Area';
require __DIR__ . '/../includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
  <div>
    <a href="dashboard.php" style="text-decoration:none; color:var(--ink-muted); font-size:13px; font-weight:600;">&larr; Kembali ke Dashboard Admin</a>
    <h2 style="margin:4px 0 0; font-size:22px;"><?= $dataEdit ? 'Edit Data Kendaraan #' . $dataEdit['id_id'] : 'Manajemen Kendaraan &amp; Area Lokasi Parkir' ?></h2>
  </div>
  <a href="../public/lacak.php" target="_blank" class="btn-add" style="background:var(--accent); color:#fff; font-weight:600; text-decoration:none; padding:9px 16px; font-size:13px;">
    <?= icon('map') ?> Tampilan Publik (Lacak Kendaraan) ↗
  </a>
</div>

<?php if ($pesan || isset($_GET['pesan'])): ?>
  <div style="background:var(--success-soft); color:var(--success); border:1px solid rgba(21,128,61,0.2); padding:12px 16px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600; font-size:13.5px;">
    ✓ <?= $pesan ?: 'Data kendaraan berhasil diperbarui.' ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:var(--danger-soft); color:var(--danger); border:1px solid rgba(185,28,28,0.2); padding:12px 16px; border-radius:var(--radius-sm); margin-bottom:20px; font-weight:600;">
    ✕ <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<!-- Form Tambah / Edit Kendaraan dengan Pilihan Area Lokasi Parkir -->
<div class="card" style="margin-bottom:28px; border: 1.5px solid var(--border);">
  <h3 style="margin-top:0; margin-bottom:16px; font-size:16px; display:flex; align-items:center; gap:8px;">
    <?= icon('car') ?> <?= $dataEdit ? 'Ubah Informasi Kendaraan #' . $dataEdit['id_kendaraan'] : 'Tambah Kendaraan Baru &amp; Lokasi Parkir' ?>
  </h3>
  
  <form method="post" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto; gap:14px; align-items:end;">
    <input type="hidden" name="id_kendaraan" value="<?= $dataEdit['id_kendaraan'] ?? 0 ?>">
    
    <div>
      <label style="margin-top:0;">Plat Nomor Kendaraan</label>
      <input type="text" name="plat_nomor" value="<?= htmlspecialchars($dataEdit['plat_nomor'] ?? '') ?>" placeholder="mis. B 1234 ABC" required style="text-transform:uppercase; font-weight:700;">
    </div>
    
    <div>
      <label style="margin-top:0;">Jenis Kendaraan</label>
      <select name="jenis_kendaraan" required>
        <?php foreach (['mobil' => 'Mobil', 'motor' => 'Motor', 'lainnya' => 'Lainnya / Bus'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= (($dataEdit['jenis_kendaraan'] ?? '') === $val) ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="margin-top:0;">Warna Kendaraan</label>
      <input type="text" name="warna" value="<?= htmlspecialchars($dataEdit['warna'] ?? '') ?>" placeholder="mis. Hitam Metalik">
    </div>

    <?php if (!$dataEdit): ?>
      <div>
        <label style="margin-top:0; color:var(--accent-dark); font-weight:700;">📍 Masukkan Ke Area Parkir</label>
        <select name="id_area" style="border-color:var(--accent); background:var(--accent-soft);">
          <option value="0">-- Simpan Master Saja (Tanpa Parkir) --</option>
          <?php foreach ($areaList as $a): ?>
            <option value="<?= $a['id_area'] ?>"><?= htmlspecialchars($a['nama_area']) ?> (sisa <?= $a['kapasitas'] - $a['terisi'] ?> slot)</option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div>
      <button type="submit" class="btn-add" style="height:42px; width:100%; justify-content:center; margin-top:0; font-weight:700;">
        <?= $dataEdit ? 'Simpan Perubahan' : '+ Tambah &amp; Masukkan Parkir' ?>
      </button>
    </div>
  </form>
</div>

<!-- Filter Pencarian & Tabel Daftar Kendaraan -->
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
    <h3 style="margin:0; font-size:16px;">Daftar Master Kendaraan (<?= count($daftar) ?> Data)</h3>
    <form method="get" style="display:flex; gap:8px;">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari plat / warna..." style="padding:7px 12px; font-size:13px; width:220px;">
      <button type="submit" class="btn-add" style="padding:7px 14px; font-size:13px;">Cari</button>
      <?php if ($q !== ''): ?>
        <a href="kendaraan.php" class="btn btn-edit" style="text-decoration:none; display:inline-flex; align-items:center;">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div style="overflow-x:auto;">
    <table style="width:100%;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Plat Nomor</th>
          <th>Jenis</th>
          <th>Warna</th>
          <th>Status Parkir Saat Ini</th>
          <th>Verifikasi Lacak Publik</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($daftar as $k): ?>
          <tr>
            <td>#<?= $k['id_kendaraan'] ?></td>
            <td><strong style="font-family:var(--font-mono); font-size:14px;"><?= htmlspecialchars($k['plat_nomor']) ?></strong></td>
            <td><span class="badge badge-success"><?= htmlspecialchars(ucfirst($k['jenis_kendaraan'])) ?></span></td>
            <td><?= htmlspecialchars($k['warna'] ?: '-') ?></td>
            <td>
              <?php if (!empty($k['status_parkir']) && $k['status_parkir'] === 'masuk'): ?>
                <span class="badge badge-warning">Parkir di <?= htmlspecialchars($k['nama_area']) ?></span>
              <?php else: ?>
                <span class="badge badge-info" style="background:var(--surface-alt); color:var(--ink-muted);">Tidak Parkir</span>
              <?php endif; ?>
            </td>
            <td>
              <a href="../public/lacak.php?plat_nomor=<?= urlencode($k['plat_nomor']) ?>" target="_blank" style="font-size:12.5px; color:var(--accent-dark); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                🔍 Lacak di Publik ↗
              </a>
            </td>
            <td style="white-space:nowrap;">
              <?php if (!empty($k['status_parkir']) && $k['status_parkir'] === 'masuk'): ?>
                <a class="btn-add" href="kendaraan.php?aksi=keluar&id=<?= $k['id_kendaraan'] ?>" onclick="return confirm('Proses KELUAR untuk kendaraan <?= htmlspecialchars($k['plat_nomor']) ?>? Total biaya akan dihitung otomatis!')" style="padding:4px 10px; font-size:12px; background:#D97706; text-decoration:none; font-weight:700;">🚪 Keluar &amp; Bayar</a>
                <a class="btn btn-edit" href="struk.php?id=<?= $k['id_parkir'] ?>" target="_blank" style="padding:4px 8px; font-size:12px;">🖨️ Struk</a>
              <?php endif; ?>
              <a class="btn btn-edit" href="kendaraan.php?aksi=edit&id=<?= $k['id_kendaraan'] ?>">Edit</a>
              <a class="btn btn-del" href="kendaraan.php?aksi=hapus&id=<?= $k['id_kendaraan'] ?>" onclick="return confirm('Hapus kendaraan <?= htmlspecialchars($k['plat_nomor']) ?> dan riwayatnya?')">Hapus</a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$daftar): ?>
          <tr><td colspan="8" style="text-align:center; color:var(--ink-muted); padding:24px;">Tidak ada data kendaraan yang ditemukan.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
