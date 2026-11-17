<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['owner'], app_path('auth/login_admin.php'));

$stmt = $pdo->query("
    SELECT 
        t.id_transaksi,
        t.tanggal_sewa,
        t.tanggal_kembali,
        t.created_at AS tanggal_transaksi,
        t.status,
        u.nama,
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    ORDER BY t.created_at DESC
");
$laporan = $stmt->fetchAll();

// Fitur Ekspor Laporan CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_transaksi_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
    fputcsv($out, ['No', 'ID Transaksi', 'Tanggal Transaksi', 'Penyewa', 'Peralatan', 'Harga Sewa', 'Jumlah', 'Subtotal', 'Tanggal Sewa', 'Tanggal Kembali', 'Status']);
    $no = 1;
    foreach ($laporan as $row) {
        fputcsv($out, [
            $no++,
            $row['id_transaksi'],
            $row['tanggal_transaksi'],
            $row['nama'],
            $row['nama_peralatan'],
            $row['harga_sewa'],
            $row['jumlah'],
            $row['subtotal'],
            $row['tanggal_sewa'],
            $row['tanggal_kembali'],
            $row['status']
        ]);
    }
    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi - Owner Tulip.projectt</title>
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
        <a href="laporan_transaksi.php" class="sidebar-nav-item active">
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
        <div class="page-header d-flex justify-between align-center">
            <div>
                <h1 class="page-title">📑 Laporan Transaksi Penyewaan</h1>
                <p class="page-subtitle">Rekapitulasi lengkap seluruh penyewaan peralatan piknik.</p>
            </div>
            <a href="?export=csv" class="btn btn-success">
                📥 Ekspor Data (CSV)
            </a>
        </div>

        <div class="checkout-card">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Transaksi</th>
                            <th>Penyewa</th>
                            <th>Gambar</th>
                            <th>Peralatan</th>
                            <th>Jumlah</th>
                            <th>Total Biaya</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($laporan)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted">Belum ada transaksi recorded.</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $no = 1;
                            foreach ($laporan as $row):
                                $st = strtolower($row['status']);
                                $bClass = 'badge-warning';
                                if ($st === 'selesai' || $st === 'disetujui') $bClass = 'badge-success';
                                if ($st === 'dibatalkan') $bClass = 'badge-danger';

                                $gambar = $row['gambar']
                                    ? app_path("uploads/peralatan/" . SecurityHelper::e($row['gambar']))
                                    : app_path("assets/img/no-image.png");
                            ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td class="cell-nowrap"><?= SecurityHelper::e(date('d M Y, H:i', strtotime($row['tanggal_transaksi']))) ?></td>
                                <td><strong><?= SecurityHelper::e($row['nama']) ?></strong></td>
                                <td class="text-center">
                                    <img src="<?= $gambar ?>" class="img-product-table" alt="Item">
                                </td>
                                <td><?= SecurityHelper::e($row['nama_peralatan']) ?></td>
                                <td class="text-bold"><?= (int)$row['jumlah'] ?> unit</td>
                                <td class="text-bold text-primary"><?= SecurityHelper::rupiah($row['subtotal']) ?></td>
                                <td>
                                    <span class="badge <?= $bClass ?>">
                                        <?= strtoupper(SecurityHelper::e($row['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detail_transaksi.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-outline btn-sm">
                                        Rincian
                                    </a>
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
