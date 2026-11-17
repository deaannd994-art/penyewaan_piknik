<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama = $_SESSION['nama'] ?? 'Pelanggan';

$stmt = $pdo->query("
    SELECT * FROM peralatan
    WHERE status = 'tersedia'
    ORDER BY nama_peralatan ASC
");
$peralatan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Peralatan - Tulip.projectt</title>
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
            <a href="peralatan.php" class="nav-link active">Peralatan</a>
            <a href="keranjang.php" class="nav-link">🛒 Keranjang</a>
            <a href="riwayat.php" class="nav-link">📜 Riwayat</a>
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

        <!-- PAGE TITLE -->
        <div class="page-header">
            <h1 class="page-title">🧺 Katalog Paket Peralatan Piknik</h1>
            <p class="page-subtitle">Pilih peralatan atau paket set piknik favorit untuk momen bahagiamu.</p>
        </div>

        <!-- LIVE SEARCH & FILTER BAR -->
        <div class="catalog-filter-card">
            <div class="filter-row">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="catalogSearch" placeholder="Cari nama set, tikar, peralatan makan..." autocomplete="off">
                </div>

                <select id="catalogStockFilter" class="filter-select">
                    <option value="all">Semua Status Ketersediaan</option>
                    <option value="available">Hanya yang Tersedia</option>
                    <option value="empty">Stok Habis</option>
                </select>

                <select id="catalogSort" class="filter-select">
                    <option value="default">Urutan Default</option>
                    <option value="price-asc">Harga: Termurah ke Termahal</option>
                    <option value="price-desc">Harga: Termahal ke Termurah</option>
                    <option value="name-asc">Nama: A - Z</option>
                </select>
            </div>
            <div class="filter-stats mt-2" id="catalogFilterCount">
                Menampilkan <?= count($peralatan) ?> dari <?= count($peralatan) ?> paket
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <div class="product-grid" id="catalogGrid">
            <?php foreach ($peralatan as $row): ?>
            <div class="card-piknik"
                 data-name="<?= SecurityHelper::e($row['nama_peralatan']) ?>"
                 data-desc="<?= SecurityHelper::e($row['deskripsi']) ?>"
                 data-price="<?= (int)$row['harga_sewa'] ?>"
                 data-stock="<?= (int)$row['stok'] ?>"
                 data-status="<?= SecurityHelper::e($row['status']) ?>">

                <div class="card-img-wrapper">
                    <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                         alt="<?= SecurityHelper::e($row['nama_peralatan']) ?>" 
                         loading="lazy">
                    <div class="card-floating-badge">
                        <?php if ($row['status'] === 'tersedia' && (int)$row['stok'] > 0): ?>
                            <span class="badge badge-success">Tersedia (<?= (int)$row['stok'] ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Stok Habis</span>
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

        <!-- EMPTY STATE (SEARCH RESULT) -->
        <div class="empty-state d-none" id="catalogEmptyState">
            <div class="empty-state-icon">🔍</div>
            <div class="empty-state-title">Peralatan Tidak Ditemukan</div>
            <p class="empty-state-desc">Maaf, kami tidak menemukan peralatan yang sesuai dengan kata kunci pencarian Anda.</p>
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
