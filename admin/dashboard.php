<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

/* =========================
   1. STATISTIK RINGKAS
========================= */
// Total Pendapatan Selesai
$stmtPendapatan = $pdo->query("SELECT SUM(total_harga) AS total FROM transaksi WHERE status = 'selesai'");
$totalPendapatan = (int)($stmtPendapatan->fetch()['total'] ?? 0);

// Pesanan Menunggu
$stmtMenunggu = $pdo->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'menunggu'");
$pesananMenunggu = (int)($stmtMenunggu->fetch()['total'] ?? 0);

// Peralatan Tersedia
$stmtStok = $pdo->query("SELECT SUM(stok) AS total FROM peralatan WHERE status = 'tersedia'");
$peralatanTersedia = (int)($stmtStok->fetch()['total'] ?? 0);

// Total Percobaan Serangan Diblokir
$stmtSerangan = $pdo->query("
    SELECT COUNT(*) AS total FROM audit_logs 
    WHERE status = 'failed' 
       OR action LIKE '%BLOCKED%' 
       OR action LIKE '%ATTEMPT%' 
       OR action LIKE '%SQLI%' 
       OR action LIKE '%IDOR%'
");
$seranganDiblokir = (int)($stmtSerangan->fetch()['total'] ?? 0);

/* =========================
   2. DATA GRAFIK (30 HARI TERAKHIR)
========================= */
$stmtGrafik = $pdo->query("
    SELECT 
        DATE(created_at) AS tanggal,
        SUM(total_harga) AS total
    FROM transaksi
    WHERE status = 'selesai'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tanggal ASC
");

$labels = [];
$dataPendapatan = [];

for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($tgl));
    $dataPendapatan[$tgl] = 0;
}

while ($row = $stmtGrafik->fetch()) {
    if (isset($dataPendapatan[$row['tanggal']])) {
        $dataPendapatan[$row['tanggal']] = (int)$row['total'];
    }
}
$pendapatanHarian = array_values($dataPendapatan);

/* =========================
   3. TRANSAKSI TERBARU (5 Item)
========================= */
$stmtRecent = $pdo->query("
    SELECT t.id_transaksi, t.tanggal_sewa, t.total_harga, t.status, t.created_at, u.nama AS nama_pelanggan
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recentTx = $stmtRecent->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- NAVBAR -->
<header class="site-header">
    <div class="nav-container">
        <div class="brand-logo">
            <span class="basket-icon">🧺</span>
            <span>Tulip.projectt</span>
            <span class="brand-badge">Admin Panel</span>
        </div>
        <div class="nav-actions">
            <span class="user-dropdown-btn">🛡️ <?= SecurityHelper::e($_SESSION['nama'] ?? 'Admin') ?></span>
            <a href="<?= app_path('auth/logout.php') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</header>

<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-title">Menu Utama</div>
        <a href="dashboard.php" class="sidebar-nav-item active">
            <span>🏠</span> Dashboard
        </a>
        <a href="peralatan/index.php" class="sidebar-nav-item">
            <span>🧺</span> Peralatan
        </a>
        <a href="transaksi/index.php" class="sidebar-nav-item">
            <span>💳</span> Transaksi
        </a>
        <a href="users/index.php" class="sidebar-nav-item">
            <span>👤</span> Data Users
        </a>
        <div class="sidebar-title">Keamanan & Audit</div>
        <a href="audit_logs.php" class="sidebar-nav-item">
            <span>🛡️</span> Security Audit Logs
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <div class="page-header">
            <h1 class="page-title">Selamat datang, <?= SecurityHelper::e($_SESSION['nama'] ?? 'Admin') ?> 👋</h1>
            <p class="page-subtitle">Ringkasan operasional penyewaan peralatan piknik dan pemantauan sistem.</p>
        </div>

        <!-- 4-CARD STATISTICS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon">💰</div>
                <div class="stat-card-label">Total Pendapatan</div>
                <div class="stat-card-value"><?= SecurityHelper::rupiah($totalPendapatan) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon warning">⏳</div>
                <div class="stat-card-label">Pesanan Menunggu</div>
                <div class="stat-card-value"><?= number_format($pesananMenunggu) ?> Transaksi</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon info">📦</div>
                <div class="stat-card-label">Peralatan Tersedia</div>
                <div class="stat-card-value"><?= number_format($peralatanTersedia) ?> Unit</div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon danger">🛑</div>
                <div class="stat-card-label">Serangan Cyber Diblokir</div>
                <div class="stat-card-value"><?= number_format($seranganDiblokir) ?> Percobaan</div>
            </div>
        </div>

        <!-- GRAFIK PENDAPATAN (ZERO INLINE SCRIPT VIA DATA ATTRIBUTES) -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">📊 Tren Pendapatan Harian (30 Hari Terakhir)</h3>
                <span class="badge badge-success">Metrik Aktif</span>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartHarian" 
                        data-labels='<?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP) ?>'
                        data-values='<?= json_encode($pendapatanHarian) ?>'>
                </canvas>
            </div>
        </div>

        <!-- TABEL TRANSAKSI TERBARU -->
        <div class="checkout-card">
            <div class="d-flex justify-between align-center mb-3">
                <h3 class="checkout-card-title mb-0">📑 Transaksi Terbaru</h3>
                <a href="transaksi/index.php" class="btn btn-outline btn-sm">Kelola Semua Transaksi →</a>
            </div>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Sewa</th>
                            <th>Total Tagihan</th>
                            <th>Status Pesanan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTx)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada transaksi tercatat.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach ($recentTx as $tx): 
                                $st = strtolower($tx['status']);
                                $bClass = 'badge-warning';
                                if ($st === 'selesai' || $st === 'disetujui') $bClass = 'badge-success';
                                if ($st === 'dibatalkan') $bClass = 'badge-danger';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= SecurityHelper::e($tx['nama_pelanggan']) ?></strong></td>
                                <td class="cell-nowrap"><?= SecurityHelper::e(date('d M Y', strtotime($tx['tanggal_sewa']))) ?></td>
                                <td class="text-bold text-primary"><?= SecurityHelper::rupiah($tx['total_harga']) ?></td>
                                <td>
                                    <span class="badge <?= $bClass ?>">
                                        <?= SecurityHelper::e(strtoupper($tx['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="transaksi/detail.php?id=<?= urlencode($tx['id_transaksi']) ?>" class="btn btn-outline btn-sm">
                                        Detail
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
