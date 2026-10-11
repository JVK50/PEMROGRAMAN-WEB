<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/csrf.php';
require __DIR__ . '/../includes/koneksi.php';

csrf_verify();

$anggotaId = $_POST['anggota_id'] ?? '';
$bukuId = $_POST['buku_id'] ?? '';

if ($anggotaId === '' || $bukuId === '') {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Anggota dan buku wajib dipilih.'];
    header('Location: tambah.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Soal 1: tolak jika anggota punya peminjaman aktif yang terlambat > 14 hari
    $cekTerlambat = $pdo->prepare(
        "SELECT COUNT(*)
         FROM peminjaman
         WHERE anggota_id = :anggota_id
           AND status = 'dipinjam'
           AND (CURRENT_DATE - tanggal_pinjam) > 14"
    );
    $cekTerlambat->execute(['anggota_id' => $anggotaId]);

    if ((int) $cekTerlambat->fetchColumn() > 0) {
        throw new Exception('Anggota memiliki peminjaman terlambat lebih dari 14 hari. Kembalikan buku terlebih dahulu.');
    }

    // Soal 3: kunci baris buku agar stok tidak berubah oleh transaksi lain
    $cek = $pdo->prepare("SELECT stok FROM buku WHERE id = :id FOR UPDATE");
    $cek->execute(['id' => $bukuId]);
    $buku = $cek->fetch(PDO::FETCH_ASSOC);

    if (!$buku || $buku['stok'] < 1) {
        throw new Exception('Stok buku tidak tersedia.');
    }

    // Soal 2: tanggal_jatuh_tempo otomatis 14 hari setelah tanggal_pinjam
    $insert = $pdo->prepare(
        "INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
         VALUES (:buku_id, :anggota_id, CURRENT_DATE, CURRENT_DATE + INTERVAL '14 days', 'dipinjam')"
    );
    $insert->execute(['buku_id' => $bukuId, 'anggota_id' => $anggotaId]);

    $update = $pdo->prepare("UPDATE buku SET stok = stok - 1 WHERE id = :id");
    $update->execute(['id' => $bukuId]);

    $pdo->commit();

    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Peminjaman berhasil dicatat.'];
    header('Location: ../index.php');
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Gagal mencatat peminjaman: ' . $e->getMessage()];
    header('Location: tambah.php');
    exit;
}