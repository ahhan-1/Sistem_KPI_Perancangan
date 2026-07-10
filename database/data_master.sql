USE kpi_kp_dft;

-- ============================================================
-- DATA MASTER SISTEM KPI KP DFT
-- Sumber: Dump langsung dari database kpi_kp_dft
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE users;
TRUNCATE TABLE sasaran_strategis;
TRUNCATE TABLE subperspektif;
TRUNCATE TABLE perspektif;
TRUNCATE TABLE cabang;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. Tabel Cabang (27 Kantor Cabang)
-- ============================================================
INSERT INTO `cabang` (`id_cabang`, `kode_cabang`, `nama_cabang`) VALUES
(1,  '140', 'Kapten A. Rivai'),
(2,  '141', 'Baturaja'),
(3,  '142', 'Lahat'),
(4,  '143', 'Lubuklinggau'),
(5,  '144', 'Pangkalpinang'),
(6,  '145', 'Sungailiat'),
(7,  '146', 'Tanjung Pandan'),
(8,  '147', 'Muara Enim'),
(9,  '148', 'Kayu Agung'),
(10, '149', 'Sekayu'),
(11, '150', 'Palembang'),
(12, '151', 'Prabumulih'),
(13, '152', 'Pagar Alam'),
(14, '154', 'Muara Dua'),
(15, '155', 'Toboali'),
(16, '157', 'Pendopo Pali'),
(17, '161', 'Koba'),
(18, '162', 'Mentok'),
(19, '163', 'Manggar'),
(20, '166', 'Martapura'),
(21, '167', 'Pangkalan Balai'),
(22, '170', 'Jakarta'),
(23, '171', 'Indralaya'),
(24, '173', 'Tebing Tinggi'),
(25, '191', 'Muara Rupit'),
(26, '193', 'Jakabaring'),
(27, '200', 'Muara Beliti');

-- ============================================================
-- 2. Tabel Perspektif
-- ============================================================
INSERT INTO `perspektif` (`id_perspektif`, `nama_perspektif`) VALUES
(1, 'Financial'),
(2, 'Customer'),
(3, 'Internal Bussines Process'),
(4, 'Learning & Growth');

-- ============================================================
-- 3. Tabel Subperspektif
-- ============================================================
INSERT INTO `subperspektif` (`id_subperspektif`, `id_perspektif`, `nama_subperspektif`) VALUES
(1, 1, 'Laba'),
(2, 2, 'Penghimpunan Dana'),
(3, 2, 'Pertumbuhan Kredit'),
(4, 2, 'Kualitas Kredit'),
(5, 3, 'Zero Fraud & Sanksi Regulasi Bank'),
(6, 4, 'Produktifitas Pegawai & Culture'),
(7, 4, 'Hasil Kinerja Layanan');

-- ============================================================
-- 4. Tabel Sasaran Strategis (Tahun 2026)
-- ============================================================
INSERT INTO `sasaran_strategis` (`id_sasaran`, `id_perspektif`, `tahun`, `nama_sasaran`) VALUES
(1, 1, 2026, 'Profitability Meningkat'),
(2, 2, 2026, 'Pertumbuhan Pangsa Pasar Dana Pihak Ketiga'),
(3, 2, 2026, 'Pertumbuhan Pangsa Pasar Kredit/Pembiayaan Konsumtif'),
(4, 2, 2026, 'Pertumbuhan Pangsa Pasar Kredit/Pembiayaan Produktif'),
(5, 2, 2026, 'Kualitas Kredit'),
(6, 3, 2026, 'Impementasi Governance Risk & Compliance'),
(7, 4, 2026, 'Peningkatan Kompetensi Pegawai di Cabang'),
(8, 4, 2026, 'Penguatan Budaya Kerja'),
(9, 4, 2026, 'Peningkatan Kualitas Layanan');

-- ============================================================
-- 5. Tabel Users
--    Password default semua: 12345 (bcrypt hash)
-- ============================================================
INSERT INTO `users` (`id_user`, `id_cabang`, `username`, `password`, `jabatan`, `nama`, `direktorat`, `pangkat`, `level_kip`, `atasan_langsung`, `atasan_dari_atasan_langsung`, `direktur_utama`, `NIP`, `no_hp`, `tanggal_menjabat`, `tanggal_masuk_kerja`, `tanggal_pengangkatan_terakhir`, `tahun`) VALUES
-- Divisi & Direksi
(1,  NULL, 'DivisiBKU', '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Divisi',          'Administrator BKU', NULL,     NULL,  NULL,    NULL,           NULL,      NULL,               NULL,           NULL,           NULL,         NULL,         NULL,         2026),
(2,  NULL, 'Direksi',   '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Direksi',         'Dewan Direksi',     NULL,     NULL,  NULL,    NULL,           NULL,      NULL,               NULL,           NULL,           NULL,         NULL,         NULL,         2026),
-- Pemimpin Cabang (27 cabang)
(3,  1,    'PemimpinCabangKaptenA.Rivai',   '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Ahmad Fauzi',       'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1987041532', '081372564918', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(4,  2,    'PemimpinCabangBaturaja',         '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Muhammad Rizki',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1991126847', '082184735629', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(5,  3,    'PemimpinCabangLahat',            '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Dimas Saputra',     'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1979083251', '085267314890', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(6,  4,    'PemimpinCabangLubuklinggau',     '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Andika Pratama',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '2001037496', '081578426193', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(7,  5,    'PemimpinCabangPangkalpinang',    '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Rudi Hartono',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1985129374', '083817295604', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(8,  6,    'PemimpinCabangSungailiat',       '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Fajar Ramadhan',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1998062145', '082396184725', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(9,  7,    'PemimpinCabangTanjungPandan',    '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Ilham Maulana',     'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1977115689', '085712638491', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(10, 8,    'PemimpinCabangMuaraEnim',        '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Reza Pahlevi',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '2000124837', '081245973618', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(11, 9,    'PemimpinCabangKayuAgung',        '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Arif Hidayat',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1983096751', '082175864309', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(12, 10,   'PemimpinCabangSekayu',           '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Wahyu Setiawan',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1995048216', '085381729546', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(13, 11,   'PemimpinCabangPalembang',        '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Adi Nugroho',       'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1976023948', '081963274185', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(14, 12,   'PemimpinCabangPrabumulih',       '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Bayu Kurniawan',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '2002081573', '082287154693', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(15, 13,   'PemimpinCabangPagarAlam',        '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'M. Iqbal',          'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1988114269', '085674219837', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(16, 14,   'PemimpinCabangMuaraDua',         '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Firman Syahputra',  'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1993075184', '081754382961', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(17, 15,   'PemimpinCabangToboali',          '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Dani Saputra',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1979052637', '083156927480', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(18, 16,   'PemimpinCabangPendopoPali',      '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Rio Pratama',       'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '2001108942', '082913675248', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(19, 17,   'PemimpinCabangKoba',             '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Rizky Ananda',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1984067315', '085228741593', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(20, 18,   'PemimpinCabangMentok',           '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Yoga Prasetyo',     'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1999124856', '081486295731', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(21, 19,   'PemimpinCabangManggar',          '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Taufik Hidayat',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1978039142', '082765193824', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(22, 20,   'PemimpinCabangMartapura',        '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Andi Saputra',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '2000013768', '085917362485', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(23, 21,   'PemimpinCabangPangkalanBalai',   '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Siti Aisyah',       'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1987102459', '081329574618', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(24, 22,   'PemimpinCabangJakarta',          '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Nurul Hidayah',     'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1996057381', '083284716395', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(25, 23,   'PemimpinCabangIndralaya',        '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Putri Maharani',    'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1974128965', '082641938257', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(26, 24,   'PemimpinCabangTebingTinggi',     '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Dewi Lestari',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '2003045172', '085362874109', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(27, 25,   'PemimpinCabangMuaraRupit',       '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Rina Oktavia',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1989036214', '081857294631', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(28, 26,   'PemimpinCabangJakabaring',       '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Elsa Fitriani',     'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1991084753', '082174638592', '2026-01-05', '2024-07-01', '2025-12-02', 2026),
(29, 27,   'PemimpinCabangMuaraBeliti',      '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', 'Nabila Zahra',      'Bisnis', 'AVP', 'AVP 1', 'Bima Saputra', 'Marzuki', 'Muhammad Suryadi', '1977063489', '085743921864', '2026-01-05', '2024-07-01', '2025-12-02', 2026);
