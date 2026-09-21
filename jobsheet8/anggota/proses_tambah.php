<?php
session_start();
require __DIR__ . '/../includes/koneksi.php';

$nama = trim($_POST['nama'] ?? '');
$noAnggota = trim($_POST['no_anggota'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$noHp = trim($_POST['no_hp'] ?? '');

$errors = [];
if ($nama === '') {
    $errors[] = "Nama wajib diisi.";
}

// Penambahan format huruf/angka/tdhub
if ($noAnggota === '') {
    $errors[] = "No. Anggota wajib diisi.";
} elseif (!preg_match('/^[a-zA-Z0-9-]+$/', $noAnggota)) {
    $errors[] = "No. Anggota hanya boleh berisi huruf, angka, dan tanda hubung (-).";
}

// Validasi No.Hp
if ($noHp !== '' && !preg_match('/^[0-9\+\-\s]{8,15}$/', $noHp)) {
    $errors[] = "No. HP harus berupa angka (8-15 digit).";
}

// Validasi Alamat (Maksimal 255 karakter)
if (strlen($alamat) > 255) {
    $errors[] = "Alamat tidak boleh lebih dari 255 karakter.";
}

if (!empty($errors)) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => implode(' ', $errors)];
    header('Location: tambah.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO anggota (nama, no_anggota, alamat, no_hp)
         VALUES (:nama, :no_anggota, :alamat, :no_hp)"
    );
    $stmt->execute([
        'nama' => $nama,
        'no_anggota' => $noAnggota,
        'alamat' => $alamat,
        'no_hp' => $noHp,
    ]);
} catch (PDOException $e) {
    // 23505 = kode SQLSTATE PostgreSQL untuk unique_violation
    if ($e->getCode() === '23505') {
        $pesan = "No. Anggota sudah dipakai, gunakan nomor lain.";
    } else {
        error_log($e->getMessage());
        $pesan = "Terjadi kesalahan saat menyimpan data. Silakan coba lagi.";
    }
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => $pesan];
    header('Location: tambah.php');
    exit;
}


$_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Anggota berhasil ditambahkan.'];
header('Location: list.php');
exit;