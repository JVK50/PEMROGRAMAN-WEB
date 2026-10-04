<?php
// Header keamanan HTTP. Harus dikirim sebelum ada output HTML apa pun.
$csp = implode('; ', [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self'",
    "img-src 'self' data:",
    "object-src 'none'",
    "base-uri 'self'",
    "form-action 'self'",
    "frame-ancestors 'none'",
]);

// header('Content-Security-Policy-Report-Only: ' . $csp);
header('Content-Security-Policy: ' . $csp);
// Tahap penegakan (aktifkan setelah tahap uji bersih):
// header('Content-Security-Policy: ' . $csp);

// header('Content-Security-Policy-Report-Only: ' . $csp);
header('Content-Security-Policy: ' . $csp);