USE kpi_kp_dft;

-- Kosongkan Tabel Perspektif (Hati-hati: akan menghapus subperspektif dan indikator karena CASCADE)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE perspektif;
SET FOREIGN_KEY_CHECKS = 1;

-- Insert 4 Perspektif Balanced Scorecard
INSERT INTO perspektif (nama_perspektif) VALUES
('Financial'),
('Customer'),
('Internal Process'),
('Learning & Growth');
