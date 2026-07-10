-- SEED DATA UNTUK LOGIN SISTEM KPI KP DFT
-- Password untuk SEMUA user: password
-- Hash: $2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a

USE kpi_kp_dft;

-- 1. Bersihkan Data Lama
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE users;
TRUNCATE TABLE cabang;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Insert Data Cabang (Nama dalam format Capital Each Word)
INSERT INTO cabang (kode_cabang, nama_cabang) VALUES
('140', 'Kapten A. Rivai'),
('141', 'Baturaja'),
('142', 'Lahat'),
('143', 'Lubuklinggau'),
('144', 'Pangkalpinang'),
('145', 'Sungailiat'),
('146', 'Tanjung Pandan'),
('147', 'Muara Enim'),
('148', 'Kayu Agung'),
('149', 'Sekayu'),
('150', 'Palembang'),
('151', 'Prabumulih'),
('152', 'Pagar Alam'),
('154', 'Muara Dua'),
('155', 'Toboali'),
('157', 'Pendopo Pali'),
('161', 'Koba'),
('162', 'Mentok'),
('163', 'Manggar'),
('166', 'Martapura'),
('167', 'Pangkalan Balai'),
('170', 'Jakarta'),
('171', 'Indralaya'),
('173', 'Tebing Tinggi'),
('191', 'Muara Rupit'),
('193', 'Jakabaring'),
('200', 'Muara Beliti');

-- 3. Insert Data User
-- Akun Divisi (Username: DivisiBKU)
INSERT INTO users (username, password, jabatan, nama) 
VALUES ('DivisiBKU', '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Divisi', 'Administrator BKU');

-- Akun Direksi (Username: Direksi)
INSERT INTO users (username, password, jabatan, nama) 
VALUES ('Direksi', '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Direksi', 'Dewan Direksi');

-- Akun Pemimpin Cabang (Otomatis per Cabang)
-- Username format: PemimpinCabang[kode_cabang]
INSERT INTO users (id_cabang, username, password, jabatan, nama)
SELECT id_cabang, CONCAT('PemimpinCabang', REPLACE(nama_cabang, ' ', '')), '$2y$10$JCoUzo62koM0ug6ZRxKxRe13VK3MCphmw//0etpLqTRI0zRfI649a', 'Pemimpin Cabang', NULL
FROM cabang;
