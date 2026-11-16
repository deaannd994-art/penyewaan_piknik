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
$type         = $_GET['type'] ?? '';

if (!is_valid_uuid($id_keranjang)) {
    http_response_code(400);
    die("ID keranjang tidak valid");
}

// Ambil data keranjang milik user bersangkutan (IDOR protection)
$stmt = $pdo->prepare("
    SELECT k.id_keranjang, k.jumlah, p.stok 
    FROM keranjang k
    JOIN peralatan p ON k.id_peralatan = p.id_peralatan
    WHERE k.id_keranjang = :id_keranjang AND k.id_user = :id_user
    LIMIT 1
");
$stmt->execute([
    'id_keranjang' => $id_keranjang,
    'id_user'      => $id_user
]);
$row = $stmt->fetch();

if ($row) {
    $jumlah = (int)$row['jumlah'];
    $stok   = (int)$row['stok'];

    if ($type === 'plus' && $jumlah < $stok) {
        $up = $pdo->prepare("UPDATE keranjang SET jumlah = jumlah + 1 WHERE id_keranjang = :id AND id_user = :id_user");
        $up->execute(['id' => $id_keranjang, 'id_user' => $id_user]);
    } elseif ($type === 'minus' && $jumlah > 1) {
        $up = $pdo->prepare("UPDATE keranjang SET jumlah = jumlah - 1 WHERE id_keranjang = :id AND id_user = :id_user");
        $up->execute(['id' => $id_keranjang, 'id_user' => $id_user]);
    }
}

header("Location: keranjang.php");
exit;
