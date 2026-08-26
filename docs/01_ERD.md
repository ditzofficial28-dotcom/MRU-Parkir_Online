# Dokumentasi ERD — Aplikasi Parkir

## Entitas dan Atribut

| Entitas | Atribut Kunci | Keterangan |
|---|---|---|
| `tb_user` | id_user (PK) | Menyimpan akun admin, petugas, owner. `role` bertipe ENUM. |
| `tb_kendaraan` | id_kendaraan (PK), id_user (FK) | Data master kendaraan yang pernah masuk. |
| `tb_tarif` | id_tarif (PK) | Tarif per jam berdasarkan jenis kendaraan. |
| `tb_area_parkir` | id_area (PK) | Kapasitas dan jumlah slot terisi per area. |
| `tb_transaksi` | id_parkir (PK), id_kendaraan (FK), id_tarif (FK), id_user (FK), id_area (FK) | Transaksi parkir masuk/keluar. |
| `tb_log_aktivitas` | id_log (PK), id_user (FK) | Audit trail aktivitas setiap user. |

## Relasi Antar Tabel

- **tb_user 1—N tb_kendaraan**: satu user (yang mendaftarkan) bisa terhubung ke banyak data kendaraan.
- **tb_user 1—N tb_transaksi**: satu petugas bisa menangani banyak transaksi.
- **tb_kendaraan 1—N tb_transaksi**: satu kendaraan bisa memiliki banyak riwayat transaksi (setiap kali masuk-keluar).
- **tb_tarif 1—N tb_transaksi**: satu tarif dipakai pada banyak transaksi sesuai jenis kendaraan.
- **tb_area_parkir 1—N tb_transaksi**: satu area menampung banyak transaksi kendaraan yang parkir di area tersebut.
- **tb_user 1—N tb_log_aktivitas**: satu user memiliki banyak baris riwayat aktivitas.

## Diagram (ringkas, notasi teks)

```
tb_user (1) ───< (N) tb_kendaraan
tb_user (1) ───< (N) tb_transaksi >─── (1) tb_kendaraan
tb_tarif (1) ───< (N) tb_transaksi
tb_area_parkir (1) ───< (N) tb_transaksi
tb_user (1) ───< (N) tb_log_aktivitas
```

Diagram fisik (kolom & tipe data lengkap) mengikuti skema pada file `database/parkir.sql`
dan sesuai gambar skema database yang diberikan pada soal ujian.
