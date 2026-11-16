<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$id_user = $_SESSION['id_user'];

/* AMBIL DATA USER */
$stmt = $pdo->prepare("
    SELECT nama, email, no_hp, alamat 
    FROM users 
    WHERE id_user = :id_user
    LIMIT 1
");
$stmt->execute(['id_user' => $id_user]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die("Data pengguna tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Akun Saya</title>
    <link rel="stylesheet" href="../../assets/css/pelanggan.css">
    <link rel="stylesheet" href="../../assets/css/transaksi_sewa.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php">Home</a>
        <a href="peralatan.php">Peralatan</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="kontak.php">Kontak</a>

        <div class="akun-wrapper">
            <button class="btn-login">
                <?= e($user['nama']) ?> ▾
            </button>
            <div class="akun-menu">
                <a href="akun_saya.php">Akun Saya</a>
                <a href="../../auth/logout_pelanggan.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<!-- PAGE TITLE -->
<section class="page-title">
    <h1>👤 Informasi Akun</h1>
    <p>Data akun pelanggan</p>
</section>

<!-- CONTENT -->
<section class="info-section">
    <div class="info-container">

        <!-- INFORMASI AKUN -->
        <div class="info-left">
            <h2>Data Pribadi</h2>

            <div class="akun-info">
                <div class="akun-row">
                    <span>Nama</span>
                    <p><?= e($user['nama']) ?></p>
                </div>

                <div class="akun-row">
                    <span>Email</span>
                    <p><?= e($user['email']) ?></p>
                </div>

                <div class="akun-row">
                    <span>No. HP</span>
                    <p><?= e($user['no_hp'] ?: '-') ?></p>
                </div>

                <div class="akun-row">
                    <span>Alamat</span>
                    <p><?= nl2br(e($user['alamat'] ?: '-')) ?></p>
                </div>
            </div>
        </div>

        <!-- INFO TAMBAHAN -->
        <div class="info-right">
            <h2>Informasi</h2>
            <ul class="ketentuan-list">
                <li>Data akun bersifat pribadi dan dilindungi.</li>
                <li>Jika ada kesalahan data atau ingin mengubah nomor HP / alamat, silakan hubungi tim kami.</li>
                <li>Informasi ini digunakan untuk keperluan pengiriman dan verifikasi pesanan sewa.</li>
            </ul>
        </div>

    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt | Penyewaan Peralatan Piknik
</footer>

</body>
</html>
