<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama    = $_SESSION['nama'] ?? 'Pelanggan';
$id_user = $_SESSION['id_user'];
$csrf    = SecurityHelper::csrfToken();

$stmt = $pdo->prepare("
    SELECT 
        k.id_keranjang,
        k.jumlah,
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        p.stok,
        p.status AS status_peralatan
    FROM keranjang k
    JOIN peralatan p ON k.id_peralatan = p.id_peralatan
    WHERE k.id_user = :id_user
    ORDER BY k.created_at DESC
");
$stmt->execute(['id_user' => $id_user]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang Sewa - Tulip.projectt</title>
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
            <a href="keranjang.php" class="nav-link active">🛒 Keranjang</a>
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

        <div class="page-header">
            <h1 class="page-title">🛒 Keranjang Sewa Peralatan</h1>
            <p class="page-subtitle">Periksa paket pilihanmu, atur jumlah sewa, dan lanjutkan ke pembayaran.</p>
        </div>

        <?php if (empty($items)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🧺</div>
                <h3 class="empty-state-title">Keranjang Sewa Masih Kosong</h3>
                <p class="empty-state-desc">Kamu belum menambahkan paket piknik ke dalam keranjang. Yuk jelajahi katalog dan pilih set impianmu!</p>
                <a href="peralatan.php" class="btn btn-primary btn-lg cart-empty-btn">
                    Jelajahi Katalog Piknik
                </a>
            </div>
        <?php else: ?>
            <form action="checkout.php" method="POST">
                <?= SecurityHelper::csrfField() ?>

                <div class="catalog-filter-card mb-3">
                    <label class="d-flex align-center gap-2 cursor-pointer">
                        <input type="checkbox" id="selectAllCart" class="cart-checkbox">
                        <span class="text-bold text-sm">Pilih Semua Item untuk Checkout</span>
                    </label>
                </div>

                <div class="cart-layout">
                    <?php foreach ($items as $row): 
                        $isAvailable = ($row['status_peralatan'] === 'tersedia' && (int)$row['stok'] > 0);
                        $itemSubtotal = (float)$row['harga_sewa'] * (int)$row['jumlah'];
                    ?>
                    <div class="cart-item <?= !$isAvailable ? 'disabled' : '' ?>">
                        <input type="checkbox"
                               name="id_keranjang[]"
                               value="<?= SecurityHelper::e($row['id_keranjang']) ?>"
                               data-harga="<?= (int)$row['harga_sewa'] ?>"
                               data-jumlah="<?= (int)$row['jumlah'] ?>"
                               class="cart-checkbox"
                               <?= !$isAvailable ? 'disabled' : 'checked' ?>>

                        <div class="cart-thumb">
                            <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                                 alt="<?= SecurityHelper::e($row['nama_peralatan']) ?>">
                        </div>

                        <div class="cart-info">
                            <h4><?= SecurityHelper::e($row['nama_peralatan']) ?></h4>
                            <div class="unit-price"><?= SecurityHelper::rupiah($row['harga_sewa']) ?> / hari</div>
                            <div class="mt-1">
                                <?php if ($isAvailable): ?>
                                    <span class="badge badge-success">Tersedia (Sisa: <?= (int)$row['stok'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Stok Habis / Tidak Tersedia</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <?php if ($isAvailable): ?>
                                <div class="qty-stepper">
                                    <a href="update_qty.php?id=<?= urlencode($row['id_keranjang']) ?>&type=minus&csrf=<?= urlencode($csrf) ?>" 
                                       class="qty-btn" aria-label="Kurangi">−</a>
                                    <span class="qty-display"><?= (int)$row['jumlah'] ?></span>
                                    <a href="update_qty.php?id=<?= urlencode($row['id_keranjang']) ?>&type=plus&csrf=<?= urlencode($csrf) ?>" 
                                       class="qty-btn" aria-label="Tambah">+</a>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-danger">Habis</span>
                            <?php endif; ?>
                        </div>

                        <div class="cart-subtotal-col">
                            <span class="text-xs text-muted">Subtotal per hari:</span>
                            <div class="cart-subtotal-price"><?= SecurityHelper::rupiah($itemSubtotal) ?></div>
                            <a href="hapus_keranjang.php?id=<?= urlencode($row['id_keranjang']) ?>&csrf=<?= urlencode($csrf) ?>" 
                               class="btn btn-outline-danger btn-sm mt-1">
                                🗑️ Hapus
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- BOTTOM SUMMARY BAR -->
                <div class="catalog-filter-card mt-4 d-flex justify-between align-center">
                    <div>
                        <span class="text-muted text-sm">Estimasi Biaya Sewa / Hari:</span>
                        <div class="price-tag" id="totalHargaKeranjang">Rp 0</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        Lanjut ke Checkout Pesanan →
                    </button>
                </div>
            </form>
        <?php endif; ?>

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
