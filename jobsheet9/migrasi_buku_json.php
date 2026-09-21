<?php
require __DIR__ . '/includes/koneksi.php';

$file = __DIR__ . '/data/buku.json';
if (!is_file($file)) {
    die("File tidak ditemukan: $file\n");
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    die("JSON tidak valid: " . json_last_error_msg() . "\n");
}

$cek = $pdo->prepare(
    "SELECT 1 FROM buku WHERE judul = :judul AND pengarang = :pengarang"
);
$ins = $pdo->prepare(
    "INSERT INTO buku (judul, pengarang, tahun, stok)
     VALUES (:judul, :pengarang, :tahun, :stok)"
);

$masuk = 0;
$lewat = 0;

try {
    $pdo->beginTransaction();

    foreach ($data as $b) {
        $cek->execute(['judul' => $b['judul'], 'pengarang' => $b['pengarang']]);
        if ($cek->fetchColumn()) {
            $lewat++;
            continue;
        }

        $ins->execute([
            'judul' => $b['judul'],
            'pengarang' => $b['pengarang'],
            'tahun' => (int) $b['tahun'],
            'stok' => (int) $b['stok'],
        ]);
        $masuk++;
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Migrasi gagal dan dibatalkan: " . $e->getMessage() . "\n");
}

echo "Migrasi selesai: $masuk buku dimasukkan, $lewat dilewati (sudah ada).\n";