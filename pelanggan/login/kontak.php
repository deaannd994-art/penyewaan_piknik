<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama = $_SESSION['nama'] ?? 'Pelanggan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kontak & Bantuan - Tulip.projectt</title>
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

        <div class="page-header">
            <h1 class="page-title">📞 Layanan Pelanggan & Bantuan</h1>
            <p class="page-subtitle">Butuh bantuan seputar pesanan atau pertanyaan teknis? Kami siap melayani Anda.</p>
        </div>

        <div class="story-grid">
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📜</span> Panduan Singkat Penyewaan
                </h2>
                <ul class="criteria-list">
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Pengambilan peralatan dapat diambil sendiri di workshop atau menggunakan jasa antar.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Seluruh peralatan telah dibersihkan secara higienis sebelum diserahkan.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Harap simpan bukti sewa untuk diperlihatkan saat pengembalian barang.</li>
                </ul>
            </div>

            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>💬</span> Kontak Tulip.projectt
                </h2>
                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>📱 WhatsApp Hotline</span>
                        <strong>0831 6789 4444</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>📧 Email Support</span>
                        <strong>tulip.projectt@gmail.com</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>📍 Alamat</span>
                        <strong>Sindanglaut, Cirebon, Jawa Barat</strong>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="https://wa.me/6283167894444" target="_blank" rel="noopener noreferrer" class="btn btn-accent btn-full">
                        💬 Hubungi WhatsApp Sekarang
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
