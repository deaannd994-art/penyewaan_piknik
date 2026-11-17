<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['owner'], app_path('auth/login_admin.php'));

$stmt = $pdo->query("SELECT * FROM peralatan ORDER BY nama_peralatan ASC");
$peralatan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Peralatan - Owner Tulip.projectt</title>
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
        <a href="data_peralatan.php" class="sidebar-nav-item active">
            <span>🧺</span> Data Peralatan
        </a>
        <a href="laporan_transaksi.php" class="sidebar-nav-item">
            <span>📑</span> Laporan Transaksi
        </a>
        <a href="data_users.php" class="sidebar-nav-item">
            <span>👤</span> Data Users
        </a>
        <div class="sidebar-title">Keamanan Sistem</div>
        <a href="audit_logs.php" class="sidebar-nav-item">
            <span>🛡️</span> Security Audit Logs
        </a>
    </aside>

    <!-- CONTENT -->
    <main class="dashboard-main">
        <div class="page-header">
            <h1 class="page-title">🧺 Inventaris Peralatan & Stok</h1>
            <p class="page-subtitle">Daftar seluruh paket piknik dan ketersediaan peralatan saat ini.</p>
        </div>

        <div class="checkout-card">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Peralatan</th>
                            <th>Deskripsi Komponen</th>
                            <th>Harga Sewa / Hari</th>
                            <th>Stok</th>
                            <th>Status Ketersediaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($peralatan as $row): 
                            $isAvail = ($row['status'] === 'tersedia');
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <?php if ($row['gambar']): ?>
                                    <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                                         class="img-product-table" alt="Peralatan">
                                <?php endif; ?>
                            </td>
                            <td><strong><?= SecurityHelper::e($row['nama_peralatan']) ?></strong></td>
                            <td class="cell-details"><?= nl2br(SecurityHelper::e($row['deskripsi'])) ?></td>
                            <td class="text-bold text-primary"><?= SecurityHelper::rupiah($row['harga_sewa']) ?></td>
                            <td class="text-bold"><?= (int)$row['stok'] ?> unit</td>
                            <td>
                                <?php if ($isAvail): ?>
                                    <span class="badge badge-success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Tidak Tersedia</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
