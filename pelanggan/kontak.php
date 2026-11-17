<?php
require_once __DIR__ . '/../config/database.php';
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
            <a href="kontak.php" class="nav-link active">Kontak & Ketentuan</a>
            <div class="nav-actions">
                <a href="<?= app_path('auth/login_pelanggan.php') ?>" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= app_path('auth/register.php') ?>" class="btn btn-primary btn-sm">Daftar Akun</a>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">
        <?php require dirname(__DIR__) . '/views/partials/contact_content.php'; ?>
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
