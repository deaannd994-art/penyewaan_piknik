<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['owner'], app_path('auth/login_admin.php'));

$stmt = $pdo->query("
    SELECT id_user, nama, email, no_hp, alamat, created_at
    FROM users
    WHERE role = 'pelanggan'
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan - Owner Tulip.projectt</title>
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
        <div class="page-header">
            <h1 class="page-title">👤 Data Pelanggan Terdaftar</h1>
            <p class="page-subtitle">Informasi profil pelanggan dan akses riwayat penyewaan masing-masing pelanggan.</p>
        </div>

        <div class="checkout-card">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No HP / WhatsApp</th>
                            <th>Alamat Domisili</th>
                            <th>Tanggal Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($users as $row): 
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td><strong><?= SecurityHelper::e($row['nama']) ?></strong></td>
                            <td><?= SecurityHelper::e($row['email']) ?></td>
                            <td><?= SecurityHelper::e($row['no_hp'] ?: '-') ?></td>
                            <td class="cell-details"><?= SecurityHelper::e($row['alamat'] ?: '-') ?></td>
                            <td class="cell-nowrap"><?= SecurityHelper::e(date('d M Y', strtotime($row['created_at']))) ?></td>
                            <td>
                                <a href="detail_users.php?id=<?= urlencode($row['id_user']) ?>" class="btn btn-outline btn-sm">
                                    Detail Profil
                                </a>
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
