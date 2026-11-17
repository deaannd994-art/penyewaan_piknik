<?php
namespace Tulip\Middleware;

use PDO;

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/security.php';

/**
 * AuthMiddleware
 * Standard: OWASP A01: Broken Access Control & A07: Authentication Failures
 * Manajemen Sesi 1 Jam (3600 Detik), Idle Timeout, Deteksi Otomatis Localhost/HTTPS,
 * Pemisahan Session Cookie Admin (TULIP_ADMIN_SESS) & Pelanggan (TULIP_USER_SESS),
 * serta Smart Role-Based Access Control
 */
class AuthMiddleware {
    public const DEFAULT_LIFETIME = 3600;
    public const ADMIN_SESSION_NAME = 'TULIP_ADMIN_SESS';
    public const USER_SESSION_NAME  = 'TULIP_USER_SESS';

    /**
     * Inisialisasi Sesi Aman dengan Pemisahan Cookie Area Admin vs Pelanggan
     */
    public static function initSession(?string $area = null): void {
        init_secure_session($area);
    }

    /**
     * Mendapatkan nama cookie sesi sesuai area
     */
    public static function getSessionName(?string $area = null): string {
        return get_session_name($area);
    }

    /**
     * Mendapatkan URL Dashboard berdasarkan role pengguna
     */
    public static function getDashboardUrl(?string $role): string {
        return match ($role) {
            'admin' => app_path('admin/dashboard.php'),
            'owner' => app_path('owner/dashboard.php'),
            'pelanggan' => app_path('pelanggan/login/dashboard.php'),
            default => app_path('auth/login_pelanggan.php'),
        };
    }

    /**
     * Memeriksa Keabsahan Autentikasi dan Role
     */
    public static function check(array $allowed_roles = [], ?string $redirect_url = null): void {
        $isAdminArea = in_array('admin', $allowed_roles, true) || in_array('owner', $allowed_roles, true);
        $area = $isAdminArea ? 'admin' : 'pelanggan';
        self::initSession($area);
        global $pdo;

        $redirect_url = $redirect_url ?? app_path($isAdminArea ? 'auth/login_admin.php' : 'auth/login_pelanggan.php');

        // 1. Jika di sesi aktif ini belum login:
        if (empty($_SESSION['login']) || empty($_SESSION['id_user']) || empty($_SESSION['role'])) {
            // Cek apakah pengguna sedang login di sesi area lain (misal login pelanggan tapi akses admin)
            if ($isAdminArea && !empty($_COOKIE[self::USER_SESSION_NAME])) {
                $origSessName = session_name();
                $origSessId   = session_id();
                session_write_close();

                session_name(self::USER_SESSION_NAME);
                session_id((string) $_COOKIE[self::USER_SESSION_NAME]);
                session_start();

                $isUserLoggedIn = !empty($_SESSION['login']) && !empty($_SESSION['id_user']);
                $otherRole      = (string)($_SESSION['role'] ?? '');
                $otherUserId    = $_SESSION['id_user'] ?? null;

                session_write_close();
                session_name($origSessName);
                if ($origSessId !== '') {
                    session_id($origSessId);
                }
                session_start();

                if ($isUserLoggedIn && !in_array($otherRole, $allowed_roles, true)) {
                    if ($pdo instanceof PDO) {
                        log_audit(
                            $pdo,
                            $otherUserId,
                            'UNAUTHORIZED_ROLE_ACCESS',
                            $_SERVER['SCRIPT_NAME'] ?? 'system',
                            'failed',
                            "Current role: {$otherRole}, Required: " . implode(',', $allowed_roles)
                        );
                    }

                    $_SESSION['flash_error'] = 'Anda tidak memiliki hak akses ke halaman tersebut.';
                    $targetDashboard = self::getDashboardUrl($otherRole);

                    http_response_code(403);
                    header("Location: " . $targetDashboard, true, 403);
                    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($targetDashboard, ENT_QUOTES, 'UTF-8') ?>">
    <title>Mengalihkan Hak Akses...</title>
    <script>
        window.location.replace(<?= json_encode($targetDashboard) ?>);
    </script>
</head>
<body>
    <p>Mengalihkan ke dashboard Anda... <a href="<?= htmlspecialchars($targetDashboard, ENT_QUOTES, 'UTF-8') ?>">Klik di sini jika tidak otomatis dialihkan</a>.</p>
</body>
</html>
<?php
                    exit;
                }
            }

            // Benar-benar belum login atau sesi berakhir
            $_SESSION['flash_warning'] = 'Sesi Anda telah berakhir atau belum login. Silakan masuk terlebih dahulu.';
            header("Location: " . $redirect_url);
            exit;
        }

        $currentRole = (string) $_SESSION['role'];

        // 2. Jika sudah login tetapi role tidak diizinkan di area ini:
        if (!empty($allowed_roles) && !in_array($currentRole, $allowed_roles, true)) {
            if ($pdo instanceof PDO) {
                log_audit(
                    $pdo,
                    $_SESSION['id_user'] ?? null,
                    'UNAUTHORIZED_ROLE_ACCESS',
                    $_SERVER['SCRIPT_NAME'] ?? 'system',
                    'failed',
                    "Current role: {$currentRole}, Required: " . implode(',', $allowed_roles)
                );
            }

            $_SESSION['flash_error'] = 'Anda tidak memiliki hak akses ke halaman tersebut.';
            $targetDashboard = self::getDashboardUrl($currentRole);

            // Kirim HTTP 403 Forbidden dan Location header & HTML smart redirect
            http_response_code(403);
            header("Location: " . $targetDashboard, true, 403);
            ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($targetDashboard, ENT_QUOTES, 'UTF-8') ?>">
    <title>Mengalihkan Hak Akses...</title>
    <script>
        window.location.replace(<?= json_encode($targetDashboard) ?>);
    </script>
</head>
<body>
    <p>Mengalihkan ke dashboard Anda... <a href="<?= htmlspecialchars($targetDashboard, ENT_QUOTES, 'UTF-8') ?>">Klik di sini jika tidak otomatis dialihkan</a>.</p>
</body>
</html>
<?php
            exit;
        }

        // 3. Perbarui waktu aktivitas terakhir
        $_SESSION['last_activity'] = time();
    }

    /**
     * Mendapatkan Role Aktif dari Sesi
     */
    public static function role(?string $area = null): ?string {
        self::initSession($area);
        return $_SESSION['role'] ?? null;
    }

    /**
     * Memeriksa Apakah Pengguna Sedang Login
     */
    public static function checkLogin(?string $area = null): bool {
        self::initSession($area);
        return !empty($_SESSION['login']) && !empty($_SESSION['id_user']);
    }
}
