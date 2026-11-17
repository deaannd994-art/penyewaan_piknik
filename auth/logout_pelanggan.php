<?php
require_once __DIR__ . '/../config/database.php';
// Logout Khusus Area Pelanggan (Isolasi Multi-Tab)
init_secure_session('pelanggan');

if (!empty($_SESSION['id_user'])) {
    log_audit($pdo, $_SESSION['id_user'], 'LOGOUT_PELANGGAN', 'auth/logout_pelanggan.php', 'success');
}

$userCookie = session_name();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie($userCookie, '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: " . app_path('pelanggan/dashboard.php'));
exit;
