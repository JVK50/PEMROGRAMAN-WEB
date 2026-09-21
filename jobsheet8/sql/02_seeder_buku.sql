-- Jobsheet 8: Seeder data awal buku (PostgreSQL)
-- Jalankan: psql -d simpus_mini -f sql/02_seeder_buku.sql

INSERT INTO buku (judul, pengarang, tahun, isbn, stok, kategori) VALUES
('Laskar Pelangi', 'Andrea Hirata', 2005, NULL, 4, 'fiksi'),
('Bumi Manusia', 'Pramoedya Ananta Toer', 1980, NULL, 2, 'fiksi'),
('Negeri 5 Menara', 'Ahmad Fuadi', 2009, NULL, 0, 'fiksi'),
('Filosofi Teras', 'Henry Manampiring', 2018, NULL, 5, 'non-fiksi'),
('Ronggeng Dukuh Paruk', 'Ahmad Tohari', 1982, NULL, 1, 'fiksi'),
('Cantik Itu Luka', 'Eka Kurniawan', 2002, NULL, 3, 'fiksi'),
('Pulang', 'Tere Liye', 2015, NULL, 2, 'fiksi'),
('Sang Pemimpi', 'Andrea Hirata', 2006, NULL, 6, 'fiksi'),
('Perahu Kertas', 'Dee Lestari', 2009, NULL, 0, 'fiksi'),
('Gadis Kretek', 'Ratih Kumala', 2012, NULL, 4, 'fiksi')
ON CONFLICT DO NOTHING;
