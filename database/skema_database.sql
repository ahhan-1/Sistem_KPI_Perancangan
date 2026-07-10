CREATE DATABASE IF NOT EXISTS kpi_kp_dft;
USE kpi_kp_dft;

-- ============================================================
-- SKEMA DATABASE SISTEM KPI KP DFT
-- Sumber: Dump langsung dari database kpi_kp_dft
-- Total: 17 Tabel
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabel Perspektif
CREATE TABLE IF NOT EXISTS `perspektif` (
    `id_perspektif` INT(10) NOT NULL AUTO_INCREMENT,
    `nama_perspektif` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_perspektif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Tabel Subperspektif
CREATE TABLE IF NOT EXISTS `subperspektif` (
    `id_subperspektif` INT(10) NOT NULL AUTO_INCREMENT,
    `id_perspektif`    INT(10) NOT NULL,
    `nama_subperspektif` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_subperspektif`),
    KEY `id_perspektif` (`id_perspektif`),
    CONSTRAINT `subperspektif_ibfk_1` FOREIGN KEY (`id_perspektif`) REFERENCES `perspektif` (`id_perspektif`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Tabel Sasaran Strategis
CREATE TABLE IF NOT EXISTS `sasaran_strategis` (
    `id_sasaran`    INT(11) NOT NULL AUTO_INCREMENT,
    `id_perspektif` INT(11) DEFAULT NULL,
    `tahun`         INT(11) DEFAULT NULL,
    `nama_sasaran`  TEXT DEFAULT NULL,
    PRIMARY KEY (`id_sasaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Tabel Cabang
CREATE TABLE IF NOT EXISTS `cabang` (
    `id_cabang`   INT(10) NOT NULL AUTO_INCREMENT,
    `kode_cabang` VARCHAR(255) NOT NULL,
    `nama_cabang` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_cabang`),
    UNIQUE KEY `kode_cabang` (`kode_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Tabel Users
CREATE TABLE IF NOT EXISTS `users` (
    `id_user`                       INT(10) NOT NULL AUTO_INCREMENT,
    `id_cabang`                     INT(10) DEFAULT NULL,
    `username`                      VARCHAR(255) NOT NULL,
    `password`                      VARCHAR(255) NOT NULL,
    `jabatan`                       VARCHAR(50) NOT NULL,
    `nama`                          VARCHAR(255) DEFAULT NULL,
    `direktorat`                    VARCHAR(255) DEFAULT 'Bisnis',
    `pangkat`                       VARCHAR(255) DEFAULT 'MGR',
    `level_kip`                     VARCHAR(255) DEFAULT NULL,
    `atasan_langsung`               VARCHAR(255) DEFAULT NULL,
    `atasan_dari_atasan_langsung`   VARCHAR(255) DEFAULT NULL,
    `direktur_utama`                VARCHAR(255) DEFAULT NULL,
    `NIP`                           VARCHAR(255) DEFAULT NULL,
    `no_hp`                         VARCHAR(20) DEFAULT NULL,
    `tanggal_menjabat`              DATE DEFAULT NULL,
    `tanggal_masuk_kerja`           DATE DEFAULT NULL,
    `tanggal_pengangkatan_terakhir` DATE DEFAULT NULL,
    `tahun`                         INT(11) DEFAULT 2026,
    PRIMARY KEY (`id_user`),
    UNIQUE KEY `uq_username_tahun` (`username`, `tahun`),
    KEY `id_cabang` (`id_cabang`),
    CONSTRAINT `users_ibfk_1` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. Tabel Indikator
--    Catatan: id_cabang ditambahkan untuk mendukung indikator per-cabang
CREATE TABLE IF NOT EXISTS `indikator` (
    `id_indikator`    INT(10) NOT NULL AUTO_INCREMENT,
    `id_cabang`       INT(10) NOT NULL,
    `id_subperspektif` INT(10) NOT NULL,
    `id_sasaran`      INT(11) DEFAULT NULL,
    `tahun`           INT(11) NOT NULL,
    `nama_indikator`  VARCHAR(255) NOT NULL,
    `satuan`          ENUM('Rupiah','Satuan','Persen','Score') NOT NULL,
    `bobot`           DECIMAL(5,2) NOT NULL,
    `terbalik`        TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_indikator`),
    KEY `id_subperspektif` (`id_subperspektif`),
    KEY `fk_indikator_cabang` (`id_cabang`),
    CONSTRAINT `fk_indikator_cabang` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`) ON DELETE CASCADE,
    CONSTRAINT `indikator_ibfk_1` FOREIGN KEY (`id_subperspektif`) REFERENCES `subperspektif` (`id_subperspektif`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. Tabel Skala KPI
CREATE TABLE IF NOT EXISTS `skala_kpi` (
    `id_skala`      INT(11) NOT NULL AUTO_INCREMENT,
    `id_indikator`  INT(11) NOT NULL,
    `nilai`         INT(11) NOT NULL,
    `nilai_minimum` DECIMAL(15,2) NOT NULL,
    PRIMARY KEY (`id_skala`),
    KEY `id_indikator` (`id_indikator`),
    CONSTRAINT `skala_kpi_ibfk_1` FOREIGN KEY (`id_indikator`) REFERENCES `indikator` (`id_indikator`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. Tabel Target
CREATE TABLE IF NOT EXISTS `target` (
    `id_target`    INT(10) NOT NULL AUTO_INCREMENT,
    `id_indikator` INT(10) NOT NULL,
    `id_cabang`    INT(10) NOT NULL,
    `tahun`        INT(11) NOT NULL,
    `bulan`        INT(11) DEFAULT NULL,
    `nilai_target` DECIMAL(20,2) NOT NULL,
    PRIMARY KEY (`id_target`),
    KEY `id_indikator` (`id_indikator`),
    KEY `id_cabang` (`id_cabang`),
    CONSTRAINT `target_ibfk_1` FOREIGN KEY (`id_indikator`) REFERENCES `indikator` (`id_indikator`) ON DELETE CASCADE,
    CONSTRAINT `target_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Tabel Realisasi
CREATE TABLE IF NOT EXISTS `realisasi` (
    `id_realisasi` INT(10) NOT NULL AUTO_INCREMENT,
    `id_indikator` INT(10) NOT NULL,
    `id_cabang`    INT(10) NOT NULL,
    `realisasi`    DECIMAL(20,2) NOT NULL,
    `tahun`        INT(11) NOT NULL,
    `bulan`        INT(11) NOT NULL,
    PRIMARY KEY (`id_realisasi`),
    KEY `id_indikator` (`id_indikator`),
    KEY `id_cabang` (`id_cabang`),
    CONSTRAINT `realisasi_ibfk_1` FOREIGN KEY (`id_indikator`) REFERENCES `indikator` (`id_indikator`) ON DELETE CASCADE,
    CONSTRAINT `realisasi_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. Tabel Kompetensi
CREATE TABLE IF NOT EXISTS `kompetensi` (
    `id_kompetensi`   INT(10) NOT NULL AUTO_INCREMENT,
    `id_user`         INT(10) NOT NULL,
    `tahun`           INT(11) NOT NULL,
    `bulan`           INT(11) NOT NULL,
    `nilai_kompetensi` DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (`id_kompetensi`),
    KEY `id_user` (`id_user`),
    CONSTRAINT `kompetensi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. Tabel Kompetensi Rincian
CREATE TABLE IF NOT EXISTS `kompetensi_rincian` (
    `id_kompetensi_rincian` INT(10) NOT NULL AUTO_INCREMENT,
    `id_kompetensi`         INT(10) NOT NULL,
    `nama_kompetensi`       VARCHAR(255) NOT NULL,
    `nama_subkompetensi`    VARCHAR(255) NOT NULL,
    `nilai_subkompetensi`   DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (`id_kompetensi_rincian`),
    KEY `id_kompetensi` (`id_kompetensi`),
    CONSTRAINT `kompetensi_rincian_ibfk_1` FOREIGN KEY (`id_kompetensi`) REFERENCES `kompetensi` (`id_kompetensi`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 12. Tabel Nilai Akhir
CREATE TABLE IF NOT EXISTS `nilai_akhir` (
    `id_nilai`              INT(10) NOT NULL AUTO_INCREMENT,
    `id_user`               INT(10) NOT NULL,
    `id_kompetensi`         INT(10) DEFAULT NULL,
    `bulan`                 INT(10) NOT NULL,
    `tahun`                 INT(10) NOT NULL,
    `nilai_akhir_kinerja`   VARCHAR(255) DEFAULT NULL,
    `nilai_akhir_kpi`       VARCHAR(255) DEFAULT NULL,
    `nilai_kompetensi`      DECIMAL(5,2) DEFAULT NULL,
    `nilai_tambahan_total`  DECIMAL(5,2) DEFAULT NULL,
    `nilai_pengurang_total` DECIMAL(5,2) DEFAULT NULL,
    `indeks_nilai_akhir`    VARCHAR(255) DEFAULT NULL,
    `pertimbangan_khusus`   TEXT DEFAULT NULL,
    `rekomendasi_penilai`   TEXT DEFAULT NULL,
    PRIMARY KEY (`id_nilai`),
    KEY `id_user` (`id_user`),
    KEY `id_kompetensi` (`id_kompetensi`),
    CONSTRAINT `nilai_akhir_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
    CONSTRAINT `nilai_akhir_ibfk_2` FOREIGN KEY (`id_kompetensi`) REFERENCES `kompetensi` (`id_kompetensi`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. Tabel Nilai Tambahan
CREATE TABLE IF NOT EXISTS `nilai_tambahan` (
    `id_tambahan` INT(11) NOT NULL AUTO_INCREMENT,
    `id_nilai`    INT(11) NOT NULL,
    `tugas`       VARCHAR(255) NOT NULL,
    `nilai`       DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (`id_tambahan`),
    KEY `id_nilai` (`id_nilai`),
    CONSTRAINT `nilai_tambahan_ibfk_1` FOREIGN KEY (`id_nilai`) REFERENCES `nilai_akhir` (`id_nilai`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 14. Tabel Nilai Pengurang
CREATE TABLE IF NOT EXISTS `nilai_pengurang` (
    `id_pengurang` INT(11) NOT NULL AUTO_INCREMENT,
    `id_nilai`     INT(11) NOT NULL,
    `jenis`        VARCHAR(255) NOT NULL,
    `jumlah`       INT(11) NOT NULL,
    `bobot_faktor` DECIMAL(5,2) NOT NULL,
    `nilai_hasil`  DECIMAL(5,2) NOT NULL,
    PRIMARY KEY (`id_pengurang`),
    KEY `id_nilai` (`id_nilai`),
    CONSTRAINT `nilai_pengurang_ibfk_1` FOREIGN KEY (`id_nilai`) REFERENCES `nilai_akhir` (`id_nilai`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 15. Tabel Riwayat Pelatihan
CREATE TABLE IF NOT EXISTS `riwayat_pelatihan` (
    `id_riwayat_pelatihan` INT(11) NOT NULL AUTO_INCREMENT,
    `id_nilai`             INT(11) NOT NULL,
    `nama_bidang`          VARCHAR(255) NOT NULL,
    `nama_pelatihan`       VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_riwayat_pelatihan`),
    KEY `id_nilai` (`id_nilai`),
    CONSTRAINT `riwayat_pelatihan_ibfk_1` FOREIGN KEY (`id_nilai`) REFERENCES `nilai_akhir` (`id_nilai`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 16. Tabel Laporan Persetujuan
CREATE TABLE IF NOT EXISTS `laporan_persetujuan` (
    `id_persetujuan` INT(11) NOT NULL AUTO_INCREMENT,
    `id_user`        INT(11) NOT NULL,
    `id_cabang`      INT(11) NOT NULL,
    `tahun`          INT(11) NOT NULL,
    `bulan`          INT(11) NOT NULL,
    `jenis_laporan`  VARCHAR(50) NOT NULL,
    `berkas_pdf`     VARCHAR(255) NOT NULL,
    `status`         ENUM('dikirim','disetujui','ditolak') DEFAULT 'dikirim',
    `catatan_bku`    TEXT DEFAULT NULL,
    `tgl_unggah`     DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_persetujuan`),
    KEY `id_user` (`id_user`),
    KEY `id_cabang` (`id_cabang`),
    CONSTRAINT `laporan_persetujuan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
    CONSTRAINT `laporan_persetujuan_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 17. Tabel Laporan Sanggahan
CREATE TABLE IF NOT EXISTS `laporan_sanggahan` (
    `id_sanggahan` INT(11) NOT NULL AUTO_INCREMENT,
    `id_user`      INT(11) NOT NULL,
    `id_cabang`    INT(11) NOT NULL,
    `tahun`        INT(11) NOT NULL,
    `bulan`        INT(11) NOT NULL,
    `kategori`     VARCHAR(255) NOT NULL,
    `pesan_protes` TEXT DEFAULT NULL,
    `status`       ENUM('dikirim','disetujui','ditolak') DEFAULT 'dikirim',
    `balasan_bku`  TEXT DEFAULT NULL,
    `tgl_protes`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_sanggahan`),
    KEY `id_user` (`id_user`),
    KEY `id_cabang` (`id_cabang`),
    CONSTRAINT `laporan_sanggahan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE,
    CONSTRAINT `laporan_sanggahan_ibfk_2` FOREIGN KEY (`id_cabang`) REFERENCES `cabang` (`id_cabang`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
