<?php
/**
 * Database Connection Module
 * Standard: Modern PHP PDO with 100% Native Prepared Statements & Secret Isolation
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/security.php';

// Ambil konfigurasi dari Environment Variables (Cloud / Localhost)
$host    = (string) (env('DB_HOST') ?: env('MYSQLHOST') ?: env('MYSQL_HOST') ?: 'localhost');
$port    = (int) (env('DB_PORT') ?: env('MYSQLPORT') ?: env('MYSQL_PORT') ?: 3306);
$db      = (string) (env('DB_NAME') ?: env('MYSQLDATABASE') ?: env('MYSQL_DATABASE') ?: 'penyewaan_piknik');
$user    = (string) (env('DB_USER') ?: env('MYSQLUSER') ?: env('MYSQL_USER') ?: 'root');
$pass    = (string) (env('DB_PASS') ?? env('MYSQLPASSWORD') ?? env('MYSQL_PASSWORD') ?? '');
$charset = (string) env('DB_CHARSET', 'utf8mb4');
$isSsl   = (bool) env('DB_SSL', false) || env('DB_SSL_MODE') === 'REQUIRED';

// PDO DSN dengan host, port, dbname, dan charset
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // 100% Native Prepared Statements (OWASP A03: Injection Prevention)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_general_ci"
];

// Dukungan Opsional SSL / TLS untuk Database Cloud (TiDB Cloud, Aiven, dll.)
if ($isSsl) {
    $sslCa = env('DB_SSL_CA', null);
    if (!empty($sslCa) && file_exists($sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    } elseif (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
    } elseif (file_exists('/etc/pki/tls/certs/ca-bundle.crt')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/pki/tls/certs/ca-bundle.crt';
    }

    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool) env('DB_SSL_VERIFY', false);
    }
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Sembunyikan pesan kesalahan internal basis data dari publik (OWASP A05: Security Misconfiguration)
    error_log("Database Connection Failed: " . $e->getMessage());
    if (env('APP_DEBUG') === true || env('APP_ENV') === 'local') {
        die("Koneksi database gagal: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    die("Koneksi database gagal. Silakan pastikan konfigurasi environment database telah sesuai.");
}

function get_db(): PDO {
    global $pdo;
    return $pdo;
}

// Kompatibilitas mundur fallback prosedural jika dibutuhkan
$conn = null;
if (extension_loaded('mysqli')) {
    try {
        @$conn = new mysqli($host, $user, $pass, $db, $port);
        if ($conn->connect_error) {
            $conn = null;
        } else {
            $conn->set_charset($charset);
        }
    } catch (\Throwable $t) {
        $conn = null;
    }
}
