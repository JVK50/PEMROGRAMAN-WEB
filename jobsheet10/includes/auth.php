<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Panggil setelah require auth.php, contoh: wajibRole('admin');
function wajibRole(string ...$roleBoleh): void
{
    if (!in_array($_SESSION['role'] ?? '', $roleBoleh, true)) {
        $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Anda tidak memiliki akses untuk aksi ini.'];
        header('Location: list.php');
        exit;
    }
}