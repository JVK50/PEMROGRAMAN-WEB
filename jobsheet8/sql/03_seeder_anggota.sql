-- Jobsheet 8: Seeder data awal anggota (PostgreSQL)
-- Jalankan: psql -d simpus_mini -f sql/03_seeder_anggota.sql

INSERT INTO anggota (no_anggota, nama, alamat, no_hp) VALUES
('A001', 'Siti Aminah', 'Malang', '0812xxxx'),
('A002', 'Budi Santoso', 'Batu', '0813xxxx'),
('A003', 'Dewi Lestari', 'Malang', '0814xxxx'),
('A004', 'Rizky Firmansyah', 'Lawang', '0815xxxx')
ON CONFLICT (no_anggota) DO NOTHING;
