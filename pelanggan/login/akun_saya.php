<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$id_user = $_SESSION['id_user'];

$stmt = $pdo->prepare("
    SELECT nama, email, no_hp, alamat, created_at 
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
    <title>Akun Saya - Tulip.projectt</title>
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
            <a href="keranjang.php" class="nav-link">🛒 Keranjang</a>
            <a href="riwayat.php" class="nav-link">📜 Riwayat</a>
            <a href="kontak.php" class="nav-link">Kontak</a>

            <div class="user-dropdown">
                <button type="button" class="user-dropdown-btn">
                    <span>👤</span>
                    <span><?= SecurityHelper::e($user['nama']) ?></span>
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

        <div class="page-header">
            <h1 class="page-title">👤 Profil & Informasi Akun</h1>
            <p class="page-subtitle">Data akun pelanggan yang terdaftar pada sistem Tulip.projectt.</p>
        </div>

        <div class="story-grid">
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📋</span> Data Pribadi Pelanggan
                </h2>

                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>Nama Lengkap</span>
                        <strong><?= SecurityHelper::e($user['nama']) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Alamat Email</span>
                        <strong><?= SecurityHelper::e($user['email']) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Nomor HP / WhatsApp</span>
                        <strong><?= SecurityHelper::e($user['no_hp'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Alamat Domisili</span>
                        <div class="text-right"><?= nl2br(SecurityHelper::e($user['alamat'] ?: '-')) ?></div>
                    </div>
                    <div class="detail-spec-item">
                        <span>Terdaftar Sejak</span>
                        <strong><?= SecurityHelper::e(date('d M Y', strtotime($user['created_at']))) ?></strong>
                    </div>
                </div>
            </div>

            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>🛡️</span> Keamanan & Bantuan Akun
                </h2>
                <p class="text-muted text-sm mb-3">
                    Seluruh password Anda dienkripsi dengan standar Argon2id dan HMAC-SHA256 Pepper. Data pribadi dilindungi sesuai dengan kepatuhan OWASP Top 10.
                </p>
                <div class="mt-4">
                    <a href="kontak.php" class="btn btn-outline btn-full">
                        💬 Hubungi Bantuan Pelanggan
                    </a>
                </div>
            </div>
        </div>

    </div>
</main>

<footer class="site-footer">
    <div class="container">
        © <?= date('Y') ?> 🧺 <strong>Tulip.projectt</strong> | Sistem Informasi Penyewaan Peralatan Piknik
    </div>
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
