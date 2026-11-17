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

        <div class="page-header">
            <h1 class="page-title">📞 Kontak & Ketentuan Penyewaan</h1>
            <p class="page-subtitle">Informasi penting dan syarat layanan sebelum Anda melakukan pemesanan set piknik.</p>
        </div>

        <div class="story-grid">
            <!-- KETENTUAN -->
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📜</span> Ketentuan & Tata Tertib Sewa
                </h2>
                <ul class="criteria-list">
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Pemesanan wajib dilakukan melalui sistem resmi Tulip.projectt.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Jadwal sewa aktif setelah verifikasi bukti pembayaran oleh tim kami.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Pengambilan dan pengembalian dilakukan tepat waktu sesuai tanggal sewa.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Penyewa wajib merawat kebersihan dan keutuhan seluruh komponen set piknik.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Kerusakan atau kehilangan barang menjadi tanggung jawab penyewa sepenuhnya.</li>
                    <li class="criteria-item is-valid"><span class="criteria-icon">✓</span> Keterlambatan pengembalian tanpa konfirmasi dikenakan penyesuaian biaya sewa.</li>
                </ul>
            </div>

            <!-- KONTAK INFO -->
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>💬</span> Hubungi Tim Customer Care
                </h2>
                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>📍 Alamat Workshop</span>
                        <strong>Sindanglaut, Cirebon, Jawa Barat</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>📱 WhatsApp Resmi</span>
                        <strong>0831 6789 4444</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>📧 Email Informasi</span>
                        <strong>tulip.projectt@gmail.com</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>🕘 Jam Operasional</span>
                        <strong>08.00 – 20.00 WIB (Setiap Hari)</strong>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="https://wa.me/6283167894444" target="_blank" rel="noopener noreferrer" class="btn btn-accent btn-full">
                        💬 Chat WhatsApp Sekarang
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
