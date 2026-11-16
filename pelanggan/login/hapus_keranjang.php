<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$csrf = $_GET['csrf'] ?? '';
if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    die("403 Forbidden: Invalid CSRF Token");
}

$id_user      = $_SESSION['id_user'];
$id_keranjang = $_GET['id'] ?? '';

if (!is_valid_uuid($id_keranjang)) {
    http_response_code(400);
    die("ID keranjang tidak valid");
}

$stmt = $pdo->prepare("DELETE FROM keranjang WHERE id_keranjang = :id AND id_user = :id_user");
$stmt->execute([
    'id'      => $id_keranjang,
    'id_user' => $id_user
]);

log_audit($pdo, $id_user, 'REMOVE_FROM_CART', 'keranjang', 'success', "ID Keranjang: {$id_keranjang}");

header("Location: keranjang.php");
exit;
