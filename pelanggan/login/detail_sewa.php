<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama         = $_SESSION['nama'] ?? 'Pelanggan';
$id_user      = $_SESSION['id_user'];
$id_transaksi = $_GET['id'] ?? '';

if (!is_valid_uuid($id_transaksi)) {
    http_response_code(400);
    die("400 Bad Request: ID transaksi tidak valid.");
}

/* Cek keberadaan transaksi dan kepemilikan (OWASP A01: IDOR & Access Control Protection) */
$checkTx = $pdo->prepare("SELECT id_user FROM transaksi WHERE id_transaksi = :id_tx LIMIT 1");
$checkTx->execute(['id_tx' => $id_transaksi]);
$txOwner = $checkTx->fetch();

if (!$txOwner) {
    http_response_code(404);
    die("404 Not Found: Data penyewaan tidak ditemukan.");
}

if ($txOwner['id_user'] !== $id_user) {
    log_audit($pdo, $id_user, 'IDOR_ACCESS_ATTEMPT', 'pelanggan/login/detail_sewa.php', 'failed', "Target TX: {$id_transaksi}");
    http_response_code(403);
    die("403 Forbidden: Anda tidak memiliki izin untuk mengakses data penyewaan milik pengguna lain.");
}

/* QUERY (IDOR Protected: Hanya transaksi milik user ini) */
$stmt = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        t.tanggal_sewa,
        t.tanggal_kembali,
        t.alamat,
        t.catatan,
        t.metode_pembayaran,
        t.status,
        t.total_harga,
        t.created_at AS tanggal_transaksi,
        t.bukti_pembayaran,
        p.nama_peralatan,
        p.gambar,
        p.harga_sewa,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE t.id_transaksi = :id_tx
      AND t.id_user = :id_user
");
$stmt->execute([
    'id_tx'   => $id_transaksi,
    'id_user' => $id_user
]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    http_response_code(404);
    die("404 Not Found: Rincian item penyewaan tidak ditemukan.");
}

$first = $rows[0];
$buktiFile = $first['bukti_pembayaran'] ? basename($first['bukti_pembayaran']) : null;
$buktiPath = $buktiFile ? "../../uploads/bukti/" . $buktiFile : null;
$buktiExists = $buktiPath && file_exists(__DIR__ . '/' . $buktiPath);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Sewa</title>
<link rel="stylesheet" href="../../assets/css/pelanggan.css">
<link rel="stylesheet" href="../../assets/css/transaksi_sewa.css">
<style>
.img-bukti {
    max-width: 300px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
</style>
</head>
<body>

<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php">Home</a>
        <a href="peralatan.php">Peralatan</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="riwayat.php" class="active">Riwayat</a>
        <a href="kontak.php">Kontak</a>

        <div class="akun-wrapper">
            <button class="btn-login"><?= e($nama) ?> ▾</button>
            <div class="akun-menu">
                <a href="akun_saya.php">Akun Saya</a>
                <a href="../../auth/logout_pelanggan.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<section class="checkout-page">
<div class="checkout-wrapper">
<div class="card">

<h3>Detail Penyewaan</h3>

<?php foreach ($rows as $item): ?>
<div class="ringkasan-item">
    <img src="../../uploads/peralatan/<?= e($item['gambar']) ?>" alt="<?= e($item['nama_peralatan']) ?>">

    <div>
        <strong><?= e($item['nama_peralatan']) ?></strong><br>
        Rp <?= number_format((float)$item['harga_sewa'], 0, ',', '.') ?> × <?= (int)$item['jumlah'] ?>
    </div>

    <strong>Rp <?= number_format((float)$item['subtotal'], 0, ',', '.') ?></strong>
</div>
<?php endforeach; ?>

<p><b>Tanggal Sewa :</b> <?= date('d-m-Y', strtotime($first['tanggal_sewa'])) ?></p>
<p><b>Tanggal Kembali :</b> <?= date('d-m-Y', strtotime($first['tanggal_kembali'])) ?></p>
<p><b>Alamat :</b> <?= e($first['alamat']) ?></p>
<p><b>Catatan :</b> <?= e($first['catatan'] ?: 'Tidak ada catatan') ?></p>
<p><b>Pembayaran :</b> <?= ucfirst(e($first['metode_pembayaran'])) ?></p>
<p><b>Status :</b> <span class="status <?= strtolower(e($first['status'])) ?>"><?= ucfirst(e($first['status'])) ?></span></p>
<p><b>Tanggal Transaksi :</b> <?= date('d-m-Y H:i', strtotime($first['tanggal_transaksi'])) ?></p>

<hr>

<h2 style="text-align:right;color:#d81b60">
Total: Rp <?= number_format((float)$first['total_harga'], 0, ',', '.') ?>
</h2>

<!-- BUKTI PEMBAYARAN -->
<div class="card" style="margin-top: 20px;">
    <h3>Bukti Pembayaran</h3>

    <?php if ($buktiExists): ?>
        <img src="<?= e($buktiPath) ?>" alt="Bukti Pembayaran" class="img-bukti">
    <?php else: ?>
        <p><em>Bukti pembayaran belum diupload atau metode COD.</em></p>
    <?php endif; ?>
</div>

<a href="riwayat.php" class="btn-kembali" style="display:inline-block; margin-top:20px;">← Kembali</a>

</div>
</div>
</section>

</body>
</html>
