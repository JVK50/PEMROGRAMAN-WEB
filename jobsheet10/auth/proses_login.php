<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/koneksi.php';
require __DIR__ . '/../includes/remember.php';

const MAKS_GAGAL   = 5;   // percobaan gagal berturut-turut
const KUNCI_DETIK  = 60;  // lama penguncian

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$kunci    = strtolower($username);

$gagal = $_SESSION['login_gagal'][$kunci] ?? ['jumlah' => 0, 'terakhir' => 0];

// Masih dalam masa penguncian?
if ($gagal['jumlah'] >= MAKS_GAGAL) {
    $sisa = KUNCI_DETIK - (time() - $gagal['terakhir']);
    if ($sisa > 0) {
        $_SESSION['flash'] = ['type' => 'error', 'pesan' => "Terlalu banyak percobaan gagal untuk username ini. Coba lagi dalam {$sisa} detik."];
        header('Location: login.php');
        exit;
    }
    $gagal = ['jumlah' => 0, 'terakhir' => 0]; // masa kunci habis, reset
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($password, $user['password'])) {
    unset($_SESSION['login_gagal'][$kunci]);
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];

    if (!empty($_POST['ingat'])) {
        buatRememberToken($pdo, (int) $user['id']);
    }

    header('Location: ../index.php');
    exit;
}

// Gagal: tambah penghitung
$gagal['jumlah']++;
$gagal['terakhir'] = time();
$_SESSION['login_gagal'][$kunci] = $gagal;

if ($gagal['jumlah'] >= MAKS_GAGAL) {
    $pesan = "Terlalu banyak percobaan gagal. Login dikunci selama " . KUNCI_DETIK . " detik.";
} else {
    $sisa = MAKS_GAGAL - $gagal['jumlah'];
    $pesan = "Username atau password salah. Sisa percobaan: {$sisa}.";
}

$_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Username atau password salah.'];
header('Location: login.php');
exit;