<?php
/**
 * Skrip Simulasi Soal No. 2:
 * "Tambah kolom 'jatuh tempo' (otomatis 14 hari setelah tanggal_pinjam)
 *  lalu tampilkan di kembali.php untuk melihat transaksi mana yang lewat jatuh tempo"
 */

require __DIR__ . '/includes/koneksi.php';

function garis($len = 80) {
    echo str_repeat("=", $len) . "\n";
}

function subgaris($len = 80) {
    echo str_repeat("-", $len) . "\n";
}

garis();
echo "       SIMULASI SOAL NO. 2 (KOLOM JATUH TEMPO OTOMATIS 14 HARI)\n";
garis();

// 1. Periksa struktur tabel peminjaman
echo "\n[LANGKAH 1] Memeriksa Struktur Kolom pada Tabel 'peminjaman'...\n";
$stmt = $pdo->query("
    SELECT column_name, data_type, is_nullable
    FROM information_schema.columns
    WHERE table_name = 'peminjaman'
    ORDER BY ordinal_position
");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

printf("%-5s | %-22s | %-15s\n", "No", "Nama Kolom", "Tipe Data");
subgaris(48);
$no = 1;
$foundJatuhTempo = false;
foreach ($columns as $col) {
    if ($col['column_name'] === 'tanggal_jatuh_tempo') {
        $foundJatuhTempo = true;
        printf("\033[32m%-5s | %-22s | %-15s <-- [KOLOM SOAL 2]\033[0m\n", $no++, $col['column_name'], $col['data_type']);
    } else {
        printf("%-5s | %-22s | %-15s\n", $no++, $col['column_name'], $col['data_type']);
    }
}

if ($foundJatuhTempo) {
    echo ">> Status: Kolom 'tanggal_jatuh_tempo' SUDAH AKTIF di database PostgreSQL.\n";
} else {
    echo ">> Status: Kolom 'tanggal_jatuh_tempo' belum ditemukan!\n";
    exit(1);
}

// 2. Siapkan data dummy simulasi
echo "\n[LANGKAH 2] Menyiapkan Data Anggota & Buku Uji Coba...\n";
$pdo->beginTransaction();

// Bersihkan jika ada sisa uji sebelumnya
$pdo->exec("DELETE FROM peminjaman WHERE anggota_id IN (SELECT id FROM anggota WHERE no_anggota LIKE 'SIM-%')");
$pdo->exec("DELETE FROM anggota WHERE no_anggota LIKE 'SIM-%'");
$pdo->exec("DELETE FROM buku WHERE isbn LIKE 'SIM-%'");

$stmt = $pdo->prepare("INSERT INTO anggota (nama, no_anggota, alamat, no_hp) VALUES (:nama, :no, :alamat, :hp) RETURNING id");
$stmt->execute(['nama' => 'Andi Wijaya (Simulasi)', 'no' => 'SIM-001', 'alamat' => 'Malang', 'hp' => '081234567890']);
$anggotaNormal = $stmt->fetchColumn();

$stmt->execute(['nama' => 'Dewi Lestari (Simulasi)', 'no' => 'SIM-002', 'alamat' => 'Surabaya', 'hp' => '089876543210']);
$anggotaTerlambat = $stmt->fetchColumn();

$stmt = $pdo->prepare("INSERT INTO buku (judul, pengarang, tahun, isbn, stok, kategori) VALUES (:j, :p, :t, :isbn, :s, :k) RETURNING id");
$stmt->execute(['j' => 'Pemrograman Web Modern', 'p' => 'Dimas', 't' => 2024, 'isbn' => 'SIM-B01', 's' => 5, 'k' => 'Komputer']);
$buku1 = $stmt->fetchColumn();

$stmt->execute(['j' => 'Basis Data Lanjutan', 'p' => 'Budi', 't' => 2023, 'isbn' => 'SIM-B02', 's' => 3, 'k' => 'Komputer']);
$buku2 = $stmt->fetchColumn();

echo ">> Data Dummy Berhasil Dibuat:\n";
echo "   - Anggota 1: Andi Wijaya (ID: $anggotaNormal)\n";
echo "   - Anggota 2: Dewi Lestari (ID: $anggotaTerlambat)\n";
echo "   - Buku 1   : Pemrograman Web Modern (ID: $buku1)\n";
echo "   - Buku 2   : Basis Data Lanjutan (ID: $buku2)\n";

// 3. Simulasi Transaksi 1 (Normal / Tepat Waktu)
echo "\n[LANGKAH 3] Simulasi Peminjaman Baru (Query proses_tambah.php)...\n";
echo "Query dieksekusi:\n";
echo "INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, tanggal_jatuh_tempo, status)\n";
echo "VALUES ($buku1, $anggotaNormal, CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 'dipinjam')\n\n";

$insert1 = $pdo->prepare("
    INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
    VALUES (:buku_id, :anggota_id, CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 'dipinjam')
    RETURNING id, tanggal_pinjam, tanggal_jatuh_tempo
");
$insert1->execute(['buku_id' => $buku1, 'anggota_id' => $anggotaNormal]);
$trx1 = $insert1->fetch(PDO::FETCH_ASSOC);

$selisihHari = (strtotime($trx1['tanggal_jatuh_tempo']) - strtotime($trx1['tanggal_pinjam'])) / 86400;
echo ">> Hasil Transaksi Normal (ID: {$trx1['id']}):\n";
echo "   - Tanggal Pinjam      : {$trx1['tanggal_pinjam']} (Hari ini)\n";
echo "   - Tanggal Jatuh Tempo : {$trx1['tanggal_jatuh_tempo']}\n";
echo "   - Selisih Hari        : $selisihHari hari (Tepat 14 hari!)\n";

// 4. Simulasi Transaksi 2 (Kasus Terlambat / Lewat Jatuh Tempo)
echo "\n[LANGKAH 4] Simulasi Transaksi yang Sudah Lewat Jatuh Tempo...\n";
$insert2 = $pdo->prepare("
    INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
    VALUES (:buku_id, :anggota_id, CURRENT_DATE - INTERVAL '21 days', CURRENT_DATE - INTERVAL '7 days', 'dipinjam')
    RETURNING id, tanggal_pinjam, tanggal_jatuh_tempo
");
$insert2->execute(['buku_id' => $buku2, 'anggota_id' => $anggotaTerlambat]);
$trx2 = $insert2->fetch(PDO::FETCH_ASSOC);

echo ">> Hasil Transaksi Terlambat (ID: {$trx2['id']}):\n";
echo "   - Tanggal Pinjam      : {$trx2['tanggal_pinjam']} (21 hari yang lalu)\n";
echo "   - Tanggal Jatuh Tempo : {$trx2['tanggal_jatuh_tempo']} (Lewat 7 hari yang lalu)\n";

// 5. Simulasi Tampilan Tabel di kembali.php
echo "\n[LANGKAH 5] Menjalankan Query kembali.php dan Menampilkan Hasil ke Tabel...\n";
$sqlDasar = "SELECT p.id, b.judul, a.nama, a.no_hp, p.tanggal_pinjam, p.tanggal_jatuh_tempo
             FROM peminjaman p
             JOIN buku b ON b.id = p.buku_id
             JOIN anggota a ON a.id = p.anggota_id
             WHERE p.status = 'dipinjam' AND a.no_anggota LIKE 'SIM-%'
             ORDER BY p.tanggal_pinjam";

$stmt = $pdo->query($sqlDasar);
$daftarAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);

garis(92);
printf("%-20s | %-24s | %-12s | %-12s | %-14s\n", "Nama Anggota", "Buku", "Pinjam", "Jatuh Tempo", "Status Tampilan");
garis(92);

$today = strtotime(date('Y-m-d'));
foreach ($daftarAktif as $t) {
    $jt = strtotime($t['tanggal_jatuh_tempo']);
    $isLate = ($today > $jt);
    
    if ($isLate) {
        $statusStr = "[!] TERLAMBAT";
        printf("\033[31m%-20s | %-24s | %-12s | %-12s | %-14s\033[0m\n",
            $t['nama'],
            $t['judul'],
            $t['tanggal_pinjam'],
            $t['tanggal_jatuh_tempo'],
            $statusStr
        );
    } else {
        $statusStr = "[OK] Aman";
        printf("\033[32m%-20s | %-24s | %-12s | %-12s | %-14s\033[0m\n",
            $t['nama'],
            $t['judul'],
            $t['tanggal_pinjam'],
            $t['tanggal_jatuh_tempo'],
            $statusStr
        );
    }
}
garis(92);

echo "\n>> Keterangan Tampilan di Web (kembali.php):\n";
echo "   1. Transaksi [OK] Aman       --> Tampil normal dengan tanggal jatuh tempo.\n";
echo "   2. Transaksi [!] TERLAMBAT   --> Baris tabel diberi background merah muda dan\n";
echo "                                   muncul badge merah [Terlambat] di samping tanggal.\n";

// 6. Bersihkan data dummy
echo "\n[LANGKAH 6] Membersihkan Data Uji dari Database...\n";
$pdo->rollBack(); // Rollback transaksi dummy agar database tetap bersih!
echo ">> Transaksi simulasi di-ROLLBACK. Database Anda tetap bersih dan rapi.\n";

garis();
echo "                   SIMULASI SELESAI & BERHASIL!\n";
garis();
