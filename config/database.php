<?php
/**
 * Database Connection Module
 * Standard: Modern PHP PDO with 100% Native Prepared Statements & Secret Isolation
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/security.php';

// Ambil konfigurasi dari file rahasia .env
$host    = env('DB_HOST', 'localhost');
$port    = (int) env('DB_PORT', 3306);
$db      = env('DB_NAME', 'penyewaan_piknik');
$user    = env('DB_USER', 'root');
$pass    = env('DB_PASS', '');
$charset = env('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // 100% Native Prepared Statements (OWASP A03: Injection Prevention)
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_general_ci"
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Sembunyikan pesan kesalahan internal basis data dari publik (OWASP A05: Security Misconfiguration)
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Koneksi database gagal. Silakan pastikan konfigurasi .env sesuai dan MySQL service telah aktif di XAMPP.");
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
