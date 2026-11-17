<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama         = $_SESSION['nama'] ?? 'Pelanggan';
$id_user      = $_SESSION['id_user'];
$id_transaksi = $_GET['id'] ?? '';

if (!is_valid_uuid($id_transaksi)) {
    http_response_code(400);
    die("400 Bad Request: ID transaksi tidak valid.");
}

// OWASP A01: IDOR & Access Control Protection
$checkTx = $pdo->prepare("SELECT id_user FROM transaksi WHERE id_transaksi = :id_tx LIMIT 1");
$checkTx->execute(['id_tx' => $id_transaksi]);
$txOwner = $checkTx->fetch();

if (!$txOwner) {
    http_response_code(404);
    die("404 Not Found: Data penyewaan tidak ditemukan.");
}

if ($txOwner['id_user'] !== $id_user) {
    log_audit($pdo, $id_user, 'IDOR_ACCESS_ATTEMPT', 'pelanggan/login/detail_sewa.php', 'failed', "Target TX: {$id_transaksi}");
    http_response_code(403);
    die("403 Forbidden: Anda tidak memiliki izin untuk mengakses data penyewaan milik pengguna lain.");
}

$stmt = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        t.tanggal_sewa,
        t.tanggal_kembali,
        t.alamat,
        t.catatan,
        t.metode_pembayaran,
        t.status,
        t.total_harga,
        t.created_at AS tanggal_transaksi,
        t.bukti_pembayaran,
        p.nama_peralatan,
        p.gambar,
        p.harga_sewa,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE t.id_transaksi = :id_tx
      AND t.id_user = :id_user
");
$stmt->execute([
    'id_tx'   => $id_transaksi,
    'id_user' => $id_user
]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    http_response_code(404);
    die("404 Not Found: Rincian item penyewaan tidak ditemukan.");
}

$first = $rows[0];
$buktiFile = $first['bukti_pembayaran'] ? basename($first['bukti_pembayaran']) : null;
$buktiPath = $buktiFile ? "uploads/bukti/" . $buktiFile : null;
$buktiExists = $buktiPath && file_exists(__DIR__ . '/../../' . $buktiPath);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian Pesanan #<?= SecurityHelper::e(substr($id_transaksi, 0, 8)) ?> - Tulip.projectt</title>
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
            <a href="riwayat.php" class="nav-link active">📜 Riwayat</a>
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

        <div class="mb-3 d-flex justify-between align-center">
            <a href="riwayat.php" class="btn btn-outline btn-sm">← Kembali ke Riwayat</a>
            <div>
                <?php 
                $st = strtolower($first['status']);
                $stBadge = 'badge-warning';
                if ($st === 'selesai' || $st === 'disetujui') $stBadge = 'badge-success';
                if ($st === 'dibatalkan') $stBadge = 'badge-danger';
                ?>
                <span class="badge <?= $stBadge ?>">
                    Status: <?= SecurityHelper::e(strtoupper($first['status'])) ?>
                </span>
            </div>
        </div>

        <div class="story-grid">
            <!-- LEFT: DETAILS -->
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📑</span> Informasi Transaksi #<?= SecurityHelper::e(substr($id_transaksi, 0, 8)) ?>
                </h2>

                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>Waktu Pesanan</span>
                        <strong><?= SecurityHelper::e(date('d M Y, H:i', strtotime($first['tanggal_transaksi']))) ?> WIB</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Jadwal Sewa</span>
                        <strong><?= SecurityHelper::e(date('d M Y', strtotime($first['tanggal_sewa']))) ?> s/d <?= SecurityHelper::e(date('d M Y', strtotime($first['tanggal_kembali']))) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Metode Pembayaran</span>
                        <strong><?= SecurityHelper::e($first['metode_pembayaran'] ?: 'Bayar di Tempat') ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Alamat Pengiriman</span>
                        <div class="text-right"><?= nl2br(SecurityHelper::e($first['alamat'])) ?></div>
                    </div>
                    <?php if (!empty($first['catatan'])): ?>
                    <div class="detail-spec-item">
                        <span>Catatan Khusus</span>
                        <div class="text-right"><?= nl2br(SecurityHelper::e($first['catatan'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <h3 class="checkout-card-title mt-4">
                    <span>🧺</span> Item Paket yang Disewa
                </h3>

                <ul class="order-review-list">
                    <?php foreach ($rows as $item): ?>
                    <li class="order-review-item">
                        <div class="order-review-thumb">
                            <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($item['gambar'])) ?>" 
                                 alt="<?= SecurityHelper::e($item['nama_peralatan']) ?>">
                        </div>
                        <div class="order-review-details">
                            <div class="order-review-title"><?= SecurityHelper::e($item['nama_peralatan']) ?></div>
                            <div class="order-review-meta"><?= (int)$item['jumlah'] ?> unit × <?= SecurityHelper::rupiah($item['harga_sewa']) ?>/hari</div>
                        </div>
                        <div class="order-review-price">
                            <?= SecurityHelper::rupiah($item['subtotal']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- RIGHT: PAYMENT PROOF & TOTAL -->
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>🧾</span> Ringkasan Pembayaran
                </h2>

                <div class="summary-row total-row mb-4">
                    <span>Total Pembayaran:</span>
                    <span><?= SecurityHelper::rupiah($first['total_harga']) ?></span>
                </div>

                <h3 class="checkout-card-title">
                    <span>📸</span> Bukti Pembayaran
                </h3>

                <?php if ($buktiExists): ?>
                    <div class="text-center mt-2">
                        <img src="<?= app_path($buktiPath) ?>" alt="Bukti Pembayaran" class="img-bukti-card">
                        <p class="text-xs text-muted mt-1">File: <?= SecurityHelper::e($buktiFile) ?></p>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p class="text-muted text-sm">Tidak ada lampiran bukti transfer (atau memilih metode Bayar di Tempat/COD).</p>
                    </div>
                <?php endif; ?>
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
