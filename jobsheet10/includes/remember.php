<?php
const REMEMBER_COOKIE = 'remember_me';
const REMEMBER_HARI = 30;

// Koneksi sendiri (bukan koneksi.php) karena koneksi.php memakai die():
// kalau DB mati, guard login tetap harus jalan dan mengarahkan ke Login.
// Samakan kredensial dengan includes/koneksi.php.
function pdoRemember(): PDO
{
    $pdo = new PDO("pgsql:host=localhost;port=5432;dbname=simpus_mini", "postgres", "123");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function aturCookieRemember(string $nilai, int $kedaluwarsa): void
{
    setcookie(REMEMBER_COOKIE, $nilai, [
        'expires'  => $kedaluwarsa,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']), // hanya lewat HTTPS bila tersedia
        'httponly' => true,                      // tidak terbaca JavaScript
        'samesite' => 'Lax',
    ]);
}

function buatRememberToken(PDO $pdo, int $userId): void
{
    $selector  = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare(
        "INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
         VALUES (:u, :s, :h, NOW() + INTERVAL '" . REMEMBER_HARI . " days')"
    );
    $stmt->execute([
        'u' => $userId,
        's' => $selector,
        'h' => hash('sha256', $validator), // yang disimpan di DB hanya hash-nya
    ]);

    aturCookieRemember($selector . ':' . $validator, time() + REMEMBER_HARI * 86400);
}

function loginDariCookie(): void
{
    if (isset($_SESSION['user_id']) || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }

    $bagian = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($bagian) !== 2) {
        aturCookieRemember('', time() - 3600);
        return;
    }
    [$selector, $validator] = $bagian;

    try {
        $pdo = pdoRemember();
        $stmt = $pdo->prepare(
            "SELECT rt.id, rt.user_id, rt.token_hash, u.nama, u.role
             FROM remember_tokens rt
             JOIN users u ON u.id = rt.user_id
             WHERE rt.selector = :s AND rt.expires_at > NOW()"
        );
        $stmt->execute(['s' => $selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && hash_equals($row['token_hash'], hash('sha256', $validator))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['nama']    = $row['nama'];
            $_SESSION['role']    = $row['role'];

            // rotasi token: token lama dibuang, token baru diterbitkan
            $pdo->prepare("DELETE FROM remember_tokens WHERE id = :id")->execute(['id' => $row['id']]);
            buatRememberToken($pdo, (int) $row['user_id']);
            return;
        }

        if ($row) {
            // selector cocok tapi validator salah: indikasi token dicuri,
            // cabut semua token milik user tersebut
            $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = :u")->execute(['u' => $row['user_id']]);
        }
        aturCookieRemember('', time() - 3600);
    } catch (PDOException $e) {
        // DB mati: anggap belum login, guard akan mengarahkan ke Login
    }
}

function hapusRememberSaatLogout(): void
{
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        $selector = explode(':', $_COOKIE[REMEMBER_COOKIE], 2)[0];
        try {
            pdoRemember()->prepare("DELETE FROM remember_tokens WHERE selector = :s")->execute(['s' => $selector]);
        } catch (PDOException $e) {
        }
    }
    aturCookieRemember('', time() - 3600);
}