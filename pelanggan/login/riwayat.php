<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$id_user = $_SESSION['id_user'];
$nama    = $_SESSION['nama'] ?? 'Pelanggan';

$stmt = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        t.status,
        t.total_harga,
        t.tanggal_sewa,
        t.tanggal_kembali,
        t.created_at,
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE t.id_user = :id_user
    ORDER BY t.created_at DESC
");
$stmt->execute(['id_user' => $id_user]);
$riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Penyewaan - Tulip.projectt</title>
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
            <a href="riwayat.php" class="nav-link active">Riwayat</a>
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
            <h1 class="page-title">📜 Riwayat Penyewaan Anda</h1>
            <p class="page-subtitle">Daftar transaksi dan status pemrosesan sewa peralatan piknik Anda.</p>
        </div>

        <?php if (empty($riwayat)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3 class="empty-state-title">Belum Ada Riwayat Sewa</h3>
                <p class="empty-state-desc">Anda belum pernah melakukan pemesanan sewa peralatan piknik bersama kami. Yuk pesan set favoritmu sekarang!</p>
                <a href="peralatan.php" class="btn btn-primary btn-lg cart-empty-btn">
                    Sewa Paket Sekarang
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Paket / Peralatan</th>
                            <th>Tanggal Sewa</th>
                            <th>Tarif / Hari</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Status Pesanan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($riwayat as $row): 
                            $status = strtolower($row['status']);
                            $badgeClass = 'badge-warning';
                            if ($status === 'selesai' || $status === 'disetujui') {
                                $badgeClass = 'badge-success';
                            } elseif ($status === 'dibatalkan') {
                                $badgeClass = 'badge-danger';
                            }
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-center gap-2">
                                    <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                                         alt="<?= SecurityHelper::e($row['nama_peralatan']) ?>" 
                                         class="img-product-table">
                                    <div>
                                        <strong class="text-dark"><?= SecurityHelper::e($row['nama_peralatan']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td class="cell-nowrap">
                                <?= SecurityHelper::e(date('d M Y', strtotime($row['tanggal_sewa']))) ?> 
                                <br>
                                <span class="text-xs">s/d <?= SecurityHelper::e(date('d M Y', strtotime($row['tanggal_kembali']))) ?></span>
                            </td>
                            <td><?= SecurityHelper::rupiah($row['harga_sewa']) ?></td>
                            <td class="text-bold"><?= (int)$row['jumlah'] ?> unit</td>
                            <td class="text-bold text-primary"><?= SecurityHelper::rupiah($row['subtotal']) ?></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= SecurityHelper::e(strtoupper($row['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail_sewa.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-outline btn-sm">
                                    Rincian
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
