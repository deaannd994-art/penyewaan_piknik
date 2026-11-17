<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama = $_SESSION['nama'] ?? 'Pelanggan';

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
    <title>Dashboard Pelanggan - Tulip.projectt</title>
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
            <a href="keranjang.php" class="nav-link">Keranjang</a>
            <a href="riwayat.php" class="nav-link">Riwayat</a>
            <a href="kontak.php" class="nav-link">Kontak</a>

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

        <!-- EDITORIAL HERO SECTION -->
        <section class="hero-editorial">
            <div class="hero-grid">
                <div class="hero-text-col">
                    <div class="hero-badge">
                        <span>🌸</span>
                        <span>Halo, <?= SecurityHelper::e($nama) ?>! Siap untuk Berpiknik?</span>
                    </div>
                    <h1 class="hero-title">
                        Wujudkan Momen Piknik Cantik <span class="highlight">Tanpa Ribet</span>
                    </h1>
                    <p class="hero-description">
                        Pilih set piknik favoritmu sekarang. Kami siapkan semuanya agar kamu tinggal datang dan menikmati momen istimewa.
                    </p>
                    <div class="hero-actions">
                        <a href="peralatan.php" class="btn btn-primary btn-lg">
                            🧺 Mulai Sewa Sekarang
                        </a>
                        <a href="keranjang.php" class="btn btn-outline btn-lg">
                            🛒 Lihat Keranjang
                        </a>
                    </div>
                </div>

                <div class="hero-visual">
                    <img src="<?= app_path('assets/basic set piknik.png') ?>" alt="Set Piknik Tulip Project">
                </div>
            </div>
        </section>

        <!-- REKOMENDASI SECTION -->
        <section class="page-header">
            <div class="d-flex justify-between align-center mb-3">
                <div>
                    <h2 class="page-title">Rekomendasi Set Piknik Terpopuler</h2>
                    <p class="page-subtitle">Dipilih khusus untuk menghadirkan kencan piknik terbaik.</p>
                </div>
                <a href="peralatan.php" class="btn btn-outline btn-sm">Lihat Semua Katalog →</a>
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
                            <a href="tambah_keranjang.php?id=<?= urlencode($row['id_peralatan']) ?>&csrf=<?= urlencode(SecurityHelper::csrfToken()) ?>" class="btn btn-primary btn-sm">
                                + Keranjang
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- STORY SECTION -->
        <section class="story-section">
            <div class="story-grid">
                <div class="story-content">
                    <h2>Wujudkan Kencan Impianmu Jadi Kenyataan!</h2>
                    <p>
                        <strong>Tulip.projectt</strong> menyediakan jasa sewa set piknik dan dekorasi estetik untuk mempermudah persiapanmu, sekaligus memberikan pengalaman kencan piknik (<em>picnic-date</em>) yang unik, nyaman, dan tak terlupakan.
                    </p>
                    <div class="benefits-grid">
                        <div class="benefit-card">
                            <strong>✨ Nuansa Estetik</strong>
                            Sentuhan dekorasi hangat dan fotogenik.
                        </div>
                        <div class="benefit-card">
                            <strong>🎒 Praktis</strong>
                            Peralatan lengkap dan higienis siap pakai.
                        </div>
                    </div>
                </div>

                <div class="story-visual">
                    <img src="<?= app_path('assets/piknik date.jpg') ?>" alt="Picnic Date Estetik" loading="lazy">
                </div>
            </div>
        </section>

        <!-- MAP LOCATION SECTION -->
        <?php require dirname(__DIR__, 2) . '/views/partials/maps.php'; ?>

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
