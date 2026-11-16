<?php
require_once __DIR__ . '/../config/database.php';
init_secure_session();

if (!empty($_SESSION['id_user'])) {
    log_audit($pdo, $_SESSION['id_user'], 'LOGOUT', 'auth/logout.php', 'success');
}

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: " . app_path('auth/login_admin.php'));
exit;
