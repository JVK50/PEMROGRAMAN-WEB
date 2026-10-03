<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../includes/remember.php';
hapusRememberSaatLogout();
session_destroy();
header('Location: login.php');
exit;