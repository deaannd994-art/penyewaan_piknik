<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$csrf = $_GET['csrf'] ?? $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    die("403 Forbidden: Invalid CSRF Token");
}

$id_user      = $_SESSION['id_user'];
$id_peralatan = $_GET['id'] ?? $_POST['id_peralatan'] ?? '';

if (!is_valid_uuid($id_peralatan)) {
    http_response_code(400);
    die("ID peralatan tidak valid");
}

// Cek ketersediaan peralatan
$stmtCheck = $pdo->prepare("SELECT id_peralatan, stok, status FROM peralatan WHERE id_peralatan = :id LIMIT 1");
$stmtCheck->execute(['id' => $id_peralatan]);
$alat = $stmtCheck->fetch();

if (!$alat || $alat['status'] !== 'tersedia' || (int)$alat['stok'] <= 0) {
    die("Peralatan saat ini tidak tersedia untuk disewa.");
}

// Cek apakah item sudah ada di keranjang user
$cek = $pdo->prepare("
    SELECT id_keranjang, jumlah 
    FROM keranjang 
    WHERE id_user = :id_user AND id_peralatan = :id_peralatan
    LIMIT 1
");
$cek->execute([
    'id_user'      => $id_user,
    'id_peralatan' => $id_peralatan
]);
$item = $cek->fetch();

if ($item) {
    // Jika sudah ada dan stok masih cukup -> tambah jumlah
    if ((int)$item['jumlah'] < (int)$alat['stok']) {
        $up = $pdo->prepare("
            UPDATE keranjang 
            SET jumlah = jumlah + 1
            WHERE id_keranjang = :id_keranjang AND id_user = :id_user
        ");
        $up->execute([
            'id_keranjang' => $item['id_keranjang'],
            'id_user'      => $id_user
        ]);
    }
} else {
    // Insert baru dengan UUID v4
    $newIdKeranjang = generate_uuid();
    $ins = $pdo->prepare("
        INSERT INTO keranjang (id_keranjang, id_user, id_peralatan, jumlah, created_at)
        VALUES (:id_keranjang, :id_user, :id_peralatan, 1, NOW())
    ");
    $ins->execute([
        'id_keranjang' => $newIdKeranjang,
        'id_user'      => $id_user,
        'id_peralatan' => $id_peralatan
    ]);
}

log_audit($pdo, $id_user, 'ADD_TO_CART', 'keranjang', 'success', "Peralatan: {$id_peralatan}");

header("Location: keranjang.php");
exit;
