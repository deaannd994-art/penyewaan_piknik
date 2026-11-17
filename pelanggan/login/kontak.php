<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama = $_SESSION['nama'] ?? 'Pelanggan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kontak & Ketentuan Sewa - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<header class="site-header">
    <div class="nav-container">
        <a href="dashboard.php" class="brand-logo">
            <span class="basket-icon">🧺</span>
            <span>Tulip.projectt</span>
        </a>

        <button class="nav-toggle" aria-label="Toggle Menu" aria-expanded="false">
            ☰
        </button>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Home</a>
            <a href="peralatan.php" class="nav-link">Peralatan</a>
            <a href="keranjang.php" class="nav-link">Keranjang</a>
            <a href="riwayat.php" class="nav-link">Riwayat</a>
            <a href="kontak.php" class="nav-link active">Kontak</a>

            <div class="user-dropdown">
                <button type="button" class="user-dropdown-btn">
                    <span>👤</span>
                    <span><?= SecurityHelper::e($nama) ?></span>
                    <span>▾</span>
                </button>
                <div class="user-dropdown-menu">
                    <a href="akun_saya.php" class="user-dropdown-item">⚙️ Akun Saya</a>
                    <a href="riwayat.php" class="user-dropdown-item">📜 Riwayat Sewa</a>
                    <div class="user-dropdown-divider"></div>
                    <a href="<?= app_path('auth/logout_pelanggan.php') ?>" class="user-dropdown-item text-danger">🚪 Logout</a>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">
        <?php require dirname(__DIR__, 2) . '/views/partials/contact_content.php'; ?>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>© <?= date('Y') ?> 🧺 <strong>Tulip.projectt</strong> | Sistem Informasi Penyewaan Peralatan Piknik</p>
    </div>
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
