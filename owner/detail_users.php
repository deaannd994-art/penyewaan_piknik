<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['owner'], app_path('auth/login_admin.php'));

$id_user = $_GET['id'] ?? null;
if (!is_valid_uuid($id_user)) {
    die('400 Bad Request: ID user tidak valid');
}

$stmtUser = $pdo->prepare("
    SELECT id_user, nama, email, no_hp, alamat, created_at
    FROM users
    WHERE id_user = :id AND role = 'pelanggan'
    LIMIT 1
");
$stmtUser->execute(['id' => $id_user]);
$user = $stmtUser->fetch();

if (!$user) {
    die('404 Not Found: User tidak ditemukan');
}

$stmtTx = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        t.created_at,
        t.status,
        p.nama_peralatan,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE t.id_user = :id
    ORDER BY t.created_at DESC
");
$stmtTx->execute(['id' => $id_user]);
$riwayat = $stmtTx->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Pelanggan: <?= SecurityHelper::e($user['nama']) ?> - Owner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="nav-container">
        <div class="brand-logo">
            <span class="basket-icon">🧺</span>
            <span>Tulip.projectt</span>
            <span class="brand-badge">Owner Panel</span>
        </div>
        <div class="nav-actions">
            <span class="user-dropdown-btn">👑 <?= SecurityHelper::e($_SESSION['nama'] ?? 'Owner') ?></span>
            <a href="<?= app_path('auth/logout.php') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</header>

<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-title">Menu Utama</div>
        <a href="dashboard.php" class="sidebar-nav-item">
            <span>🏠</span> Dashboard
        </a>
        <a href="data_peralatan.php" class="sidebar-nav-item">
            <span>🧺</span> Data Peralatan
        </a>
        <a href="laporan_transaksi.php" class="sidebar-nav-item">
            <span>📑</span> Laporan Transaksi
        </a>
        <a href="data_users.php" class="sidebar-nav-item active">
            <span>👤</span> Data Users
        </a>
        <div class="sidebar-title">Keamanan Sistem</div>
        <a href="audit_logs.php" class="sidebar-nav-item">
            <span>🛡️</span> Security Audit Logs
        </a>
    </aside>

    <!-- CONTENT -->
    <main class="dashboard-main">
        <div class="mb-3">
            <a href="data_users.php" class="btn btn-outline btn-sm">← Kembali ke Data Users</a>
        </div>

        <div class="story-grid mb-4">
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>👤</span> Profil Pelanggan: <?= SecurityHelper::e($user['nama']) ?>
                </h2>

                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>Nama Lengkap</span>
                        <strong><?= SecurityHelper::e($user['nama']) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Email Terdaftar</span>
                        <strong><?= SecurityHelper::e($user['email']) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>No. HP / WA</span>
                        <strong><?= SecurityHelper::e($user['no_hp'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Alamat</span>
                        <div class="text-right"><?= nl2br(SecurityHelper::e($user['alamat'] ?: '-')) ?></div>
                    </div>
                    <div class="detail-spec-item">
                        <span>Terdaftar Sejak</span>
                        <strong><?= SecurityHelper::e(date('d M Y, H:i', strtotime($user['created_at']))) ?> WIB</strong>
                    </div>
                </div>
            </div>

            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📊</span> Ringkasan Transaksi
                </h2>
                <div class="summary-row">
                    <span>Total Transaksi Dilakukan:</span>
                    <strong class="text-primary"><?= count($riwayat) ?> Transaksi</strong>
                </div>
            </div>
        </div>

        <!-- RIWAYAT TRANSAKSI -->
        <div class="checkout-card">
            <h2 class="checkout-card-title">
                <span>📑</span> Riwayat Pemesanan Pelanggan
            </h2>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Peralatan</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayat)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Pengguna ini belum pernah melakukan transaksi sewa.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($riwayat as $r):
                                $st = strtolower($r['status']);
                                $bClass = 'badge-warning';
                                if ($st === 'selesai' || $st === 'disetujui') $bClass = 'badge-success';
                                if ($st === 'dibatalkan') $bClass = 'badge-danger';
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="cell-nowrap"><?= SecurityHelper::e(date('d M Y', strtotime($r['created_at']))) ?></td>
                                <td>
                                    <div class="d-flex align-center gap-2">
                                        <?php if ($r['gambar']): ?>
                                            <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($r['gambar'])) ?>" 
                                                 class="img-product-table" alt="Item">
                                        <?php endif; ?>
                                        <strong><?= SecurityHelper::e($r['nama_peralatan']) ?></strong>
                                    </div>
                                </td>
                                <td class="text-bold"><?= (int)$r['jumlah'] ?> unit</td>
                                <td class="text-bold text-primary"><?= SecurityHelper::rupiah($r['subtotal']) ?></td>
                                <td>
                                    <span class="badge <?= $bClass ?>">
                                        <?= strtoupper(SecurityHelper::e($r['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<footer class="site-footer">
    © <?= date('Y') ?> Tulip.projectt | Sistem Informasi Penyewaan Peralatan Piknik
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
