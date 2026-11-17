<?php
/**
 * Security & Helper Module
 * Sistem Penyewaan Peralatan Piknik (Tulip.projectt)
 * Standards: OWASP Top 10 Hardening (A01 - A10)
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/SecurityHeaders.php';

// Terapkan HTTP Security Headers secara otomatis (OWASP A05: Security Misconfiguration - Helmet Equivalent)
function apply_security_headers(): void {
    SecurityHeaders::apply();
}
apply_security_headers();

/**
 * Mendapatkan Nama Cookie Sesi Berdasarkan Area (OWASP A07: Multi-Tab Session Isolation)
 * Admin & Owner: TULIP_ADMIN_SESS
 * Pelanggan & Publik: TULIP_USER_SESS
 */
function get_session_name(?string $area = null): string {
    if ($area === 'admin' || $area === 'owner') {
        return 'TULIP_ADMIN_SESS';
    }
    if ($area === 'pelanggan' || $area === 'user') {
        return 'TULIP_USER_SESS';
    }

    $uri     = $_SERVER['REQUEST_URI'] ?? '';
    $script  = $_SERVER['SCRIPT_NAME'] ?? '';
    $path    = str_replace('\\', '/', $script . ' ' . $uri);
    $referer = str_replace('\\', '/', $_SERVER['HTTP_REFERER'] ?? '');

    if (
        str_contains($path, '/admin/') ||
        str_contains($path, '/admin') ||
        str_contains($path, '/owner/') ||
        str_contains($path, '/owner') ||
        str_contains($path, 'login_admin.php') ||
        (str_contains($path, 'logout.php') && !str_contains($path, 'logout_pelanggan.php')) ||
        (str_contains($path, 'captcha.php') && (
            str_contains($referer, 'login_admin') ||
            str_contains($referer, '/admin') ||
            str_contains($referer, '/owner') ||
            (isset($_GET['area']) && $_GET['area'] === 'admin')
        ))
    ) {
        return 'TULIP_ADMIN_SESS';
    }

    return 'TULIP_USER_SESS';
}

// Konfigurasi Session Hardening (OWASP A07: Identification and Authentication Failures)
function init_secure_session(?string $area = null): void {
    $lifetime = (int) env('SESSION_LIFETIME', 3600);
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
             || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
             || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
             || (bool) env('APP_HTTPS', false);

    $targetSessionName = get_session_name($area);

    if (session_status() === PHP_SESSION_NONE) {
        session_name($targetSessionName);

        $cookieParams = [
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.cookie_secure', $isSecure ? '1' : '0');
        ini_set('session.gc_maxlifetime', (string)$lifetime);

        session_start();
    } elseif (session_status() === PHP_SESSION_ACTIVE && session_name() !== $targetSessionName) {
        session_write_close();
        session_name($targetSessionName);
        session_start();
    }

    // Pengecekan Idle Timeout (Maksimal 3600 Detik / 1 Jam)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $lifetime)) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
        session_start();
    }

    // Selalu perbarui waktu aktivitas terakhir saat halaman aktif diakses
    $_SESSION['last_activity'] = time();
}

// Generate RFC 4122 Compliant UUID v4 (CHAR(36))
function generate_uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // versi 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // bit 6-7 = 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Validasi UUID v4
function is_valid_uuid(?string $uuid): bool {
    if (!$uuid) return false;
    return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}

// Context-aware XSS Sanitization (OWASP A03: Injection)
function e(?string $string): string {
    return htmlspecialchars((string) $string, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
}

// CSRF Token Management (OWASP A01: Broken Access Control / CSRF)
function get_csrf_token(): string {
    init_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(get_csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool {
    init_secure_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        die("403 Forbidden: Invalid or Missing CSRF Token");
    }
}

/**
 * SecurityHelper: Object-oriented standard for XSS output escaping, CSRF, and data formatting
 * Compliant with OWASP Top 10 & Strict CSP Standards
 */
class SecurityHelper {
    public static function e(?string $string): string {
        return htmlspecialchars((string) $string, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function csrfToken(): string {
        return get_csrf_token();
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . self::e(get_csrf_token()) . '">';
    }

    public static function verifyCsrf(?string $token): bool {
        return verify_csrf_token($token);
    }

    public static function requireCsrf(): void {
        require_csrf();
    }

    public static function rupiah(float|int|string|null $amount): string {
        return 'Rp ' . number_format((float)$amount, 0, ',', '.');
    }

    public static function sanitizeInput(?string $data): string {
        return trim((string)$data);
    }

    public static function validateCaptcha(?string $input, PDO $pdo, string $targetResource): bool {
        return validate_captcha($input, $pdo, $targetResource);
    }

    public static function whatsappUrl(?string $message = null, ?string $phone = null): string {
        return whatsapp_url($message, $phone);
    }
}

/**
 * Helper Generator URL WhatsApp Click-to-Chat Aman
 */
function whatsapp_url(?string $message = null, ?string $phone = null): string {
    $phone = $phone ?: (string) ($_ENV['ADMIN_WHATSAPP'] ?? env('ADMIN_WHATSAPP', '6285717597300'));
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (str_starts_with($cleanPhone, '08')) {
        $cleanPhone = '628' . substr($cleanPhone, 2);
    }
    $url = 'https://wa.me/' . $cleanPhone;
    if (!empty($message)) {
        $url .= '?text=' . rawurlencode(trim($message));
    }
    return $url;
}

/**
 * Validasi Ketat Image CAPTCHA (OWASP Anti-Bot & Brute Force Prevention)
 * - One-time use (Anti-Replay)
 * - Expiration check (3 Menit)
 * - Case-insensitive hash_equals
 * - Audit logging pada kegagalan
 */
function validate_captcha(?string $input, PDO $pdo, string $targetResource): bool {
    init_secure_session();

    // Mode uji otomatis lingkungan lokal
    if ((env('APP_ENV') === 'local' || env('APP_DEBUG') === 'true') && 
        isset($_SERVER['HTTP_X_TEST_CAPTCHA']) && $_SERVER['HTTP_X_TEST_CAPTCHA'] === '1') {
        return true;
    }

    $sessionCode = $_SESSION['captcha_code'] ?? null;
    $expires     = $_SESSION['captcha_expires'] ?? 0;

    // One-Time Use (Anti-Replay Attack): Segera unset dari session
    unset($_SESSION['captcha_code']);
    unset($_SESSION['captcha_expires']);

    if (empty($sessionCode) || time() > $expires) {
        log_audit($pdo, null, 'CAPTCHA_VALIDATION_FAILED', $targetResource, 'failed', 'Alasan: Kode CAPTCHA kedaluwarsa atau tidak ada di sesi');
        return false;
    }

    if (empty($input)) {
        log_audit($pdo, null, 'CAPTCHA_VALIDATION_FAILED', $targetResource, 'failed', 'Alasan: Input CAPTCHA kosong');
        return false;
    }

    $inputUpper   = strtoupper(trim($input));
    $sessionUpper = strtoupper(trim($sessionCode));

    if (!hash_equals($sessionUpper, $inputUpper)) {
        log_audit($pdo, null, 'CAPTCHA_VALIDATION_FAILED', $targetResource, 'failed', "Input: {$inputUpper} vs Expected: {$sessionUpper}");
        return false;
    }

    return true;
}

// Kriptografi Password (HMAC-SHA256 Pepper + Argon2id) (OWASP A02: Cryptographic Failures)
function hash_password_peppered(string $password): string {
    $pepper = (string) env('APP_PEPPER_KEY', '');
    $peppered = hash_hmac('sha256', $password, $pepper);
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    return password_hash($peppered, $algo);
}

function verify_password_peppered(string $password, string $hash): bool {
    $pepper = (string) env('APP_PEPPER_KEY', '');
    $peppered = hash_hmac('sha256', $password, $pepper);

    // 1. Verifikasi dengan peppered hash (Argon2id atau Bcrypt)
    if (password_verify($peppered, $hash)) {
        return true;
    }

    // 2. Kompatibilitas mundur jika hash lama berupa plaintext bcrypt tanpa pepper
    return password_verify($password, $hash);
}

// Validasi Kebijakan Password Kuat (OWASP A07: Authentication Failures)
// Min. 8 karakter, minimal 1 huruf besar, 1 huruf kecil, 1 angka, dan 1 simbol
function is_strong_password(string $password): bool {
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    if (!preg_match('/[\W_]/', $password)) {
        return false;
    }
    return true;
}

// Client IP Resolver (Safe IPv4 / IPv6 extraction)
function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $candidate = trim($ips[0]);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $ip = $candidate;
        }
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
}

// Security Audit Logger (OWASP A09: Security Logging & Monitoring Failures)
function log_audit(PDO $pdo, ?string $id_user, string $action, string $target_resource, string $status = 'success', ?string $details = null): void {
    try {
        $id_log = generate_uuid();
        $ip = get_client_ip();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (id_log, id_user, action, target_resource, status, ip_address, user_agent, details, created_at)
            VALUES (:id_log, :id_user, :action, :target_resource, :status, :ip, :ua, :details, NOW())
        ");
        $stmt->execute([
            'id_log'          => $id_log,
            'id_user'         => $id_user,
            'action'          => $action,
            'target_resource' => $target_resource,
            'status'          => $status,
            'ip'              => $ip,
            'ua'              => $ua,
            'details'         => $details
        ]);
    } catch (\Throwable $th) {
        error_log("Audit log failure: " . $th->getMessage());
    }
}

// Brute-force & Rate Limiting Protection (OWASP A07: Identification and Authentication Failures)
function is_login_rate_limited(PDO $pdo, string $email, ?int $max_attempts = null, ?int $decay_minutes = null): bool {
    try {
        $max_attempts  = $max_attempts ?? (int) env('LOGIN_MAX_ATTEMPTS', 5);
        $decay_minutes = $decay_minutes ?? (int) env('LOGIN_LOCKOUT_MINUTES', 15);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM login_attempts
            WHERE email = :email
              AND attempted_at >= DATE_SUB(NOW(), INTERVAL :decay MINUTE)
        ");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':decay', $decay_minutes, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        return ($result && (int)$result['total'] >= $max_attempts);
    } catch (\Throwable $th) {
        error_log("Rate limit check error: " . $th->getMessage());
        return false;
    }
}

function record_failed_login(PDO $pdo, string $email): void {
    try {
        $id_attempt = generate_uuid();
        $ip = get_client_ip();
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (id_attempt, email, ip_address, attempted_at)
            VALUES (:id_attempt, :email, :ip, NOW())
        ");
        $stmt->execute([
            'id_attempt' => $id_attempt,
            'email'      => substr($email, 0, 100),
            'ip'         => $ip
        ]);
    } catch (\Throwable $th) {
        error_log("Record failed login error: " . $th->getMessage());
    }
}

function clear_failed_logins(PDO $pdo, string $email): void {
    try {
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = :email");
        $stmt->execute(['email' => $email]);
    } catch (\Throwable $th) {
        error_log("Clear failed logins error: " . $th->getMessage());
    }
}

// Dynamic App Path Resolver (Kesiapan Cloud Deployment: Vercel, Railway, Render)
function app_path(string $path = ''): string {
    $isVercel = !empty($_SERVER['VERCEL']) 
             || !empty($_ENV['VERCEL']) 
             || getenv('VERCEL') !== false 
             || str_contains(strtolower($_SERVER['HTTP_HOST'] ?? ''), 'vercel.app')
             || !empty($_SERVER['VERCEL_URL']);

    if ($isVercel) {
        $prefix = '';
    } else {
        $baseUrl = env('APP_URL', '');
        $prefix = '';
        if (!empty($baseUrl)) {
            $parsed = parse_url($baseUrl, PHP_URL_PATH);
            $prefix = $parsed ? rtrim($parsed, '/') : '';
        }
        if ($prefix === '' && isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/penyewaan_piknik')) {
            $prefix = '/penyewaan_piknik';
        }
    }

    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return $prefix !== '' ? $prefix : '/';
    }
    return ($prefix !== '' ? $prefix : '') . '/' . $cleanPath;
}

// Role-Based Access Control (RBAC) Hardening (OWASP A01: Broken Access Control)
function require_auth(array $allowed_roles = [], ?string $redirect_url = null): void {
    require_once dirname(__DIR__) . '/src/Middleware/AuthMiddleware.php';
    \Tulip\Middleware\AuthMiddleware::check($allowed_roles, $redirect_url);
}

// Alias middleware requireRole untuk sintaks yang lebih ekspresif
function requireRole(array $allowed_roles, ?string $redirect_url = null): void {
    require_auth($allowed_roles, $redirect_url);
}
