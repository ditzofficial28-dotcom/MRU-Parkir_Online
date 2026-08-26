-- =========================================================
-- Database: parkir
-- Aplikasi Parkir - Uji Kompetensi Keahlian RPL
-- Sesuai skema ERD pada soal (KM25.4.1.1)
-- =========================================================

CREATE DATABASE IF NOT EXISTS parkir CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE parkir;

-- ---------------------------------------------------------
-- Tabel user (3 level pengguna: admin, petugas, owner)
-- ---------------------------------------------------------
CREATE TABLE tb_user (
  id_user      INT(11) NOT NULL AUTO_INCREMENT,
  nama_lengkap VARCHAR(50) NOT NULL,
  username     VARCHAR(50) NOT NULL UNIQUE,
  password     VARCHAR(100) NOT NULL,
  role         ENUM('admin','petugas','owner') NOT NULL,
  status_aktif TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_user)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel tarif parkir
-- ---------------------------------------------------------
CREATE TABLE tb_tarif (
  id_tarif       INT(11) NOT NULL AUTO_INCREMENT,
  jenis_kendaraan ENUM('motor','mobil','lainnya') NOT NULL,
  tarif_per_jam  DECIMAL(10,0) NOT NULL,
  PRIMARY KEY (id_tarif)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel area parkir
-- ---------------------------------------------------------
CREATE TABLE tb_area_parkir (
  id_area   INT(11) NOT NULL AUTO_INCREMENT,
  nama_area VARCHAR(50) NOT NULL,
  kapasitas INT(5) NOT NULL,
  terisi    INT(5) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_area)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel kendaraan (data master kendaraan yang pernah masuk)
-- ---------------------------------------------------------
CREATE TABLE tb_kendaraan (
  id_kendaraan   INT(11) NOT NULL AUTO_INCREMENT,
  plat_nomor     VARCHAR(15) NOT NULL,
  jenis_kendaraan VARCHAR(20) NOT NULL,
  warna          VARCHAR(20),
  pemilik        VARCHAR(100),
  id_user        INT(11) NOT NULL COMMENT 'user yang mendaftarkan/mengubah data',
  PRIMARY KEY (id_kendaraan),
  CONSTRAINT fk_kendaraan_user FOREIGN KEY (id_user) REFERENCES tb_user(id_user),
  INDEX idx_plat_nomor (plat_nomor)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel transaksi parkir
-- ---------------------------------------------------------
CREATE TABLE tb_transaksi (
  id_parkir    INT(11) NOT NULL AUTO_INCREMENT,
  id_kendaraan INT(11) NOT NULL,
  waktu_masuk  DATETIME NOT NULL,
  waktu_keluar DATETIME NULL,
  id_tarif     INT(11) NOT NULL,
  durasi_jam   INT(5) NULL,
  biaya_total  DECIMAL(10,0) NULL,
  status       ENUM('masuk','keluar') NOT NULL DEFAULT 'masuk',
  id_user      INT(11) NOT NULL COMMENT 'petugas yang menangani transaksi',
  id_area      INT(11) NOT NULL,
  PRIMARY KEY (id_parkir),
  CONSTRAINT fk_transaksi_kendaraan FOREIGN KEY (id_kendaraan) REFERENCES tb_kendaraan(id_kendaraan),
  CONSTRAINT fk_transaksi_tarif FOREIGN KEY (id_tarif) REFERENCES tb_tarif(id_tarif),
  CONSTRAINT fk_transaksi_user FOREIGN KEY (id_user) REFERENCES tb_user(id_user),
  CONSTRAINT fk_transaksi_area FOREIGN KEY (id_area) REFERENCES tb_area_parkir(id_area),
  INDEX idx_status (status),
  INDEX idx_waktu_masuk (waktu_masuk),
  INDEX idx_waktu_keluar (waktu_keluar)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel log aktifitas
-- ---------------------------------------------------------
CREATE TABLE tb_log_aktivitas (
  id_log         INT(11) NOT NULL AUTO_INCREMENT,
  id_user        INT(11) NOT NULL,
  aktivitas      VARCHAR(100) NOT NULL,
  waktu_aktivitas DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_log),
  CONSTRAINT fk_log_user FOREIGN KEY (id_user) REFERENCES tb_user(id_user),
  INDEX idx_waktu_aktivitas (waktu_aktivitas)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- View: rekap pendapatan harian
-- Dipakai oleh dashboard Owner untuk menampilkan grafik tren
-- pendapatan tanpa perlu agregasi berulang di sisi aplikasi.
-- ---------------------------------------------------------
CREATE OR REPLACE VIEW v_pendapatan_harian AS
SELECT
  DATE(waktu_keluar)   AS tanggal,
  COUNT(*)             AS total_transaksi,
  SUM(biaya_total)     AS total_pendapatan
FROM tb_transaksi
WHERE status = 'keluar'
GROUP BY DATE(waktu_keluar);

-- ---------------------------------------------------------
-- Data awal (default password untuk ketiga akun = "password123")
-- SEGERA GANTI PASSWORD INI SETELAH INSTALASI DI LINGKUNGAN PRODUKSI!
-- ---------------------------------------------------------
INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES
('Administrator', 'admin', '$2b$12$t7q.S14mtncGmOpGePOHuumiCK8oLH9OcoXQtwH4GquaXKca/sVsW', 'admin', 1),
('Budi Santoso', 'petugas1', '$2b$12$t7q.S14mtncGmOpGePOHuumiCK8oLH9OcoXQtwH4GquaXKca/sVsW', 'petugas', 1),
('Siti Rahma', 'petugas2', '$2b$12$t7q.S14mtncGmOpGePOHuumiCK8oLH9OcoXQtwH4GquaXKca/sVsW', 'petugas', 1),
('Hendra Wijaya', 'owner1', '$2b$12$t7q.S14mtncGmOpGePOHuumiCK8oLH9OcoXQtwH4GquaXKca/sVsW', 'owner', 1);

INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam) VALUES
('motor', 2000),
('mobil', 5000),
('lainnya', 3000);

INSERT INTO tb_area_parkir (nama_area, kapasitas, terisi) VALUES
('Area A - Motor Lantai 1', 80, 0),
('Area B - Mobil Lantai 1', 40, 0),
('Area C - Mobil Lantai 2', 40, 0),
('Area D - Kendaraan Lainnya', 15, 0);

-- ---------------------------------------------------------
-- Data contoh kendaraan (untuk demo tampilan, boleh dihapus)
-- ---------------------------------------------------------
INSERT INTO tb_kendaraan (plat_nomor, jenis_kendaraan, warna, pemilik, id_user) VALUES
('B 1234 ABC', 'mobil', 'Hitam', 'Andi Pratama', 1),
('B 5678 XYZ', 'motor', 'Merah', 'Dewi Lestari', 1),
('D 9012 QRS', 'mobil', 'Putih', 'Rian Saputra', 1),
('B 3344 JKL', 'motor', 'Biru', 'Fajar Nugraha', 1),
('B 7788 MNO', 'mobil', 'Silver', 'Nadia Putri', 1),
('B 2255 PQR', 'motor', 'Hitam', 'Yusuf Ramadhan', 1),
('B 6611 STU', 'mobil', 'Merah', 'Lina Marlina', 1),
('B 4499 VWX', 'motor', 'Putih', 'Agus Setiawan', 1);

-- ---------------------------------------------------------
-- Data contoh transaksi 7 hari terakhir (untuk demo grafik &
-- rekap pada dashboard, boleh dihapus di lingkungan produksi)
-- ---------------------------------------------------------
INSERT INTO tb_transaksi (id_kendaraan, waktu_masuk, waktu_keluar, id_tarif, durasi_jam, biaya_total, status, id_user, id_area) VALUES
(1, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 8 HOUR,  DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 11 HOUR, 2, 3, 15000, 'keluar', 2, 2),
(2, DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 9 HOUR,  DATE_SUB(NOW(), INTERVAL 6 DAY) + INTERVAL 10 HOUR, 1, 1, 2000,  'keluar', 2, 1),
(3, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 8 HOUR,  DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 13 HOUR, 2, 5, 25000, 'keluar', 3, 3),
(4, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 5 DAY) + INTERVAL 12 HOUR, 1, 2, 4000,  'keluar', 2, 1),
(5, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 7 HOUR,  DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 15 HOUR, 2, 8, 40000, 'keluar', 3, 2),
(6, DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 9 HOUR,  DATE_SUB(NOW(), INTERVAL 4 DAY) + INTERVAL 11 HOUR, 1, 2, 4000,  'keluar', 2, 1),
(7, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 8 HOUR,  DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 12 HOUR, 2, 4, 20000, 'keluar', 3, 3),
(8, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 13 HOUR, DATE_SUB(NOW(), INTERVAL 3 DAY) + INTERVAL 14 HOUR, 1, 1, 2000,  'keluar', 2, 1),
(1, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 8 HOUR,  DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 14 HOUR, 2, 6, 30000, 'keluar', 3, 2),
(2, DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 9 HOUR,  DATE_SUB(NOW(), INTERVAL 2 DAY) + INTERVAL 10 HOUR, 1, 1, 2000,  'keluar', 2, 1),
(3, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 8 HOUR,  DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 16 HOUR, 2, 8, 40000, 'keluar', 3, 3),
(4, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 10 HOUR, DATE_SUB(NOW(), INTERVAL 1 DAY) + INTERVAL 11 HOUR, 1, 1, 2000,  'keluar', 2, 1),
(5, NOW() - INTERVAL 3 HOUR, NOW() - INTERVAL 1 HOUR, 2, 2, 10000, 'keluar', 2, 2),
(6, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR, 1, 1, 2000,  'keluar', 3, 1),
(7, NOW() - INTERVAL 90 MINUTE, NULL, 2, NULL, NULL, 'masuk', 2, 3),
(8, NOW() - INTERVAL 40 MINUTE, NULL, 1, NULL, NULL, 'masuk', 3, 1);

-- Sinkronkan kolom `terisi` pada tb_area_parkir dengan transaksi yang masih berstatus 'masuk'
UPDATE tb_area_parkir a
SET terisi = (SELECT COUNT(*) FROM tb_transaksi t WHERE t.id_area = a.id_area AND t.status = 'masuk');

-- ---------------------------------------------------------
-- Data contoh log aktivitas (untuk demo tampilan, boleh dihapus)
-- ---------------------------------------------------------
INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES
(1, 'Login ke sistem', NOW() - INTERVAL 2 HOUR),
(1, 'Menambah tarif baru: lainnya', NOW() - INTERVAL 2 HOUR),
(2, 'Login ke sistem', NOW() - INTERVAL 3 HOUR),
(2, 'Mencatat kendaraan masuk: B 4499 VWX', NOW() - INTERVAL 40 MINUTE),
(3, 'Login ke sistem', NOW() - INTERVAL 4 HOUR),
(3, 'Mencatat kendaraan masuk: B 6611 STU', NOW() - INTERVAL 90 MINUTE),
(4, 'Login ke sistem', NOW() - INTERVAL 1 HOUR);
