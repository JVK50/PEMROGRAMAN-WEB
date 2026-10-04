<?php
// Jangan tampilkan error PHP mentah ke pengguna; catat ke log server saja.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$host = "localhost";
$port = "5432";
$db   = "simpus_mini";
$user = "postgres";
$pass = "postgres";

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Detail teknis hanya ke log (terminal tempat php -S berjalan)
    error_log('Koneksi database gagal: ' . $e->getMessage());
    http_response_code(500);
    die('Terjadi gangguan pada server. Silakan coba beberapa saat lagi.');
}