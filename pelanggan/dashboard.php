<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT * FROM peralatan
    WHERE status = 'tersedia'
    ORDER BY nama_peralatan ASC
    LIMIT 3
");
$rekomendasi = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sewa Peralatan Piknik Estetik - Tulip.projectt</title>
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
            <a href="dashboard.php" class="nav-link active">Home</a>
            <a href="peralatan.php" class="nav-link">Peralatan</a>
            <a href="kontak.php" class="nav-link">Kontak & Ketentuan</a>
            <div class="nav-actions">
                <a href="<?= app_path('auth/login_pelanggan.php') ?>" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= app_path('auth/register.php') ?>" class="btn btn-primary btn-sm">Daftar Akun</a>
            </div>
        </nav>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="main-content">
    <div class="container">

        <!-- EDITORIAL HERO SECTION -->
        <section class="hero-editorial">
            <div class="hero-grid">
                <div class="hero-text-col">
                    <div class="hero-badge">
                        <span>✨</span>
                        <span>Vendor Sewa Set Piknik Estetik Cirebon</span>
                    </div>
                    <h1 class="hero-title">
                        Nikmati Momen Piknik Tanpa <span class="highlight">Ribet</span>
                    </h1>
                    <p class="hero-description">
                        Wujudkan kencan piknik impian, perayaan ulang tahun, atau kumpul sahabat dengan set piknik estetik lengkap, praktis, dan siap pakai.
                    </p>
                    <div class="hero-actions">
                        <a href="peralatan.php" class="btn btn-primary btn-lg">
                            🧺 Jelajahi Katalog
                        </a>
                        <a href="kontak.php" class="btn btn-outline btn-lg">
                            ℹ️ Ketentuan Sewa
                        </a>
                    </div>
                    <div class="hero-features">
                        <div class="hero-feature-item">
                            <span>✅</span> Peralatan Higienis
                        </div>
                        <div class="hero-feature-item">
                            <span>📸</span> Estetik & Fotogenik
                        </div>
                        <div class="hero-feature-item">
                            <span>⚡</span> Booking Cepat & Aman
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <img src="<?= app_path('assets/basic set piknik.png') ?>" alt="Set Piknik Estetik Tulip Project">
                </div>
            </div>
        </section>

        <!-- REKOMENDASI SECTION -->
        <section class="page-header">
            <div class="d-flex justify-between align-center mb-3">
                <div>
                    <h2 class="page-title">Rekomendasi Set Piknik Favorit</h2>
                    <p class="page-subtitle">Paling diminati pelanggan untuk picnic-date dan pesta santai.</p>
                </div>
                <a href="peralatan.php" class="btn btn-outline btn-sm">Lihat Semua →</a>
            </div>

            <div class="product-grid">
                <?php foreach ($rekomendasi as $row): ?>
                <div class="card-piknik">
                    <div class="card-img-wrapper">
                        <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                             alt="<?= SecurityHelper::e($row['nama_peralatan']) ?>" 
                             loading="lazy">
                        <div class="card-floating-badge">
                            <?php if ($row['status'] === 'tersedia' && (int)$row['stok'] > 0): ?>
                                <span class="badge badge-success">Sisa <?= (int)$row['stok'] ?> Unit</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Habis</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <h3 class="card-title">
                            <a href="detail_peralatan.php?id=<?= urlencode($row['id_peralatan']) ?>">
                                <?= SecurityHelper::e($row['nama_peralatan']) ?>
                            </a>
                        </h3>
                        <p class="card-desc">
                            <?= SecurityHelper::e($row['deskripsi']) ?>
                        </p>

                        <div class="card-price-row">
                            <div>
                                <div class="price-tag"><?= SecurityHelper::rupiah($row['harga_sewa']) ?></div>
                                <div class="price-unit">per hari penyewaan</div>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a href="detail_peralatan.php?id=<?= urlencode($row['id_peralatan']) ?>" class="btn btn-outline btn-sm">
                                Detail
                            </a>
                            <a href="<?= app_path('auth/login_pelanggan.php') ?>" class="btn btn-primary btn-sm">
                                + Sewa
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- STORY / ABOUT SECTION -->
        <section class="story-section">
            <div class="story-grid">
                <div class="story-content">
                    <h2>Wujudkan Kencan Impianmu Bersama Tulip.projectt</h2>
                    <p>
                        Kami hadir sebagai solusi praktis bagi Anda yang mendambakan suasana kencan piknik (<em>picnic-date</em>) yang intim, cantik, dan menyenangkan tanpa kerepotan membawa perlengkapan sendiri.
                    </p>
                    <p>
                        Setiap perlengkapan mulai dari keranjang rotan, tikar piknik water-resistant, peralatan makan kayu, bunga tulip imitasi, hingga lampu hias kami rawat dengan standar kebersihan tertinggi.
                    </p>

                    <div class="benefits-grid">
                        <div class="benefit-card">
                            <strong>✨ Nuansa Estetik Premium</strong>
                            Sentuhan dekorasi hangat yang membuat setiap sudut foto tampak mempesona.
                        </div>
                        <div class="benefit-card">
                            <strong>🎒 Praktis & Siap Pakai</strong>
                            Cukup datang ke lokasi favorit Anda, seluruh set sudah rapi dan siap digelar.
                        </div>
                        <div class="benefit-card">
                            <strong>🔒 Transaksi Terlindungi</strong>
                            Sistem pemesanan aman dengan verifikasi langsung dan pencatatan audit ketat.
                        </div>
                        <div class="benefit-card">
                            <strong>🤍 Layanan Bersahabat</strong>
                            Tim kami siap merekomendasikan spot piknik terbaik di sekitar Cirebon.
                        </div>
                    </div>
                </div>

                <div class="story-visual">
                    <img src="<?= app_path('assets/piknik date.jpg') ?>" alt="Kencan Piknik Cantik" loading="lazy">
                </div>
            </div>
        </section>

        <!-- MAP LOCATION SECTION -->
        <section class="map-section">
            <h2 class="page-title mb-2">📍 Lokasi Penjemputan / Workshop</h2>
            <p class="page-subtitle mb-3">Sindanglaut, Cirebon, Jawa Barat - Indonesia</p>
            <div class="map-embed-wrapper">
                <iframe class="map-iframe" 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.53836053651!2d108.61503457761557!3d-6.8258591507703095!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e6f1bb6e7c2c683%3A0xb4a947c8101e411b!2sPerumahan%20BTN%20Sindanglaut%20indah!5e0!3m2!1sen!2sus!4v1766491858687!5m2!1sen!2sus" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </section>

    </div>
</main>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="container">
        © <?= date('Y') ?> 🧺 <strong>Tulip.projectt</strong> | Sistem Penyewaan Peralatan Piknik Cirebon
    </div>
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
