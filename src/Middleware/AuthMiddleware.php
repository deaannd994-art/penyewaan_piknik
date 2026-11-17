<?php
namespace Tulip\Middleware;

use PDO;

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/security.php';

/**
 * AuthMiddleware
 * Standard: OWASP A01: Broken Access Control & A07: Authentication Failures
 * Manajemen Sesi 1 Jam (3600 Detik), Idle Timeout, dan Deteksi Otomatis Localhost/HTTPS
 */
class AuthMiddleware {
    public const DEFAULT_LIFETIME = 3600;

    /**
     * Inisialisasi Sesi Aman dengan Cookie Lax dan Idle Timeout 1 Jam
     */
    public static function initSession(): void {
        init_secure_session();
    }

    /**
     * Memeriksa Keabsahan Autentikasi dan Role
     */
    public static function check(array $allowed_roles = [], ?string $redirect_url = null): void {
        require_auth($allowed_roles, $redirect_url);
    }

    /**
     * Mendapatkan Role Aktif dari Sesi
     */
    public static function role(): ?string {
        self::initSession();
        return $_SESSION['role'] ?? null;
    }

    /**
     * Memeriksa Apakah Pengguna Sedang Login
     */
    public static function checkLogin(): bool {
        self::initSession();
        return !empty($_SESSION['login']) && !empty($_SESSION['id_user']);
    }
}
