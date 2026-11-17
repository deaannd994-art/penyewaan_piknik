<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['owner'], app_path('auth/login_admin.php'));

/* =========================
   1. STATISTIK RINGKAS OWNER
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
   2. DATA GRAFIK 12 BULAN TERAKHIR
========================= */
$stmtGrafik = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS bulan,
        SUM(total_harga) AS total
    FROM transaksi
    WHERE status = 'selesai'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY bulan ASC
");

$bulanLabels = [];
$dataPendapatan = [];

for ($i = 11; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $key  = date('Y-m', $time);
    $bulanLabels[] = date('M Y', $time);
    $dataPendapatan[$key] = 0;
}

while ($row = $stmtGrafik->fetch()) {
    if (isset($dataPendapatan[$row['bulan']])) {
        $dataPendapatan[$row['bulan']] = (int)$row['total'];
    }
}
$pendapatanBulanan = array_values($dataPendapatan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Owner Dashboard - Tulip.projectt</title>
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
        <a href="dashboard.php" class="sidebar-nav-item active">
            <span>🏠</span> Dashboard
        </a>
        <a href="data_peralatan.php" class="sidebar-nav-item">
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

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <div class="page-header">
            <h1 class="page-title">Selamat datang, <?= SecurityHelper::e($_SESSION['nama'] ?? 'Owner') ?> 👑</h1>
            <p class="page-subtitle">Laporan eksekutif performa bisnis penyewaan piknik dan integritas keamanan platform.</p>
        </div>

        <!-- 4-CARD STATISTICS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon">💰</div>
                <div class="stat-card-label">Total Omset Pendapatan</div>
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

        <!-- GRAFIK PENDAPATAN BULANAN (ZERO INLINE SCRIPT) -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">📊 Tren Pendapatan 12 Bulan Terakhir</h3>
                <span class="badge badge-success">Data Terverifikasi</span>
            </div>
            <div class="chart-wrapper">
                <canvas id="chartHarian" 
                        data-labels='<?= json_encode($bulanLabels, JSON_HEX_TAG | JSON_HEX_AMP) ?>'
                        data-values='<?= json_encode($pendapatanBulanan) ?>'>
                </canvas>
            </div>
        </div>

        <!-- LAPORAN CEPAT -->
        <div class="checkout-card">
            <div class="d-flex justify-between align-center mb-3">
                <h3 class="checkout-card-title mb-0">📑 Akses Laporan Cepat</h3>
                <a href="laporan_transaksi.php" class="btn btn-outline btn-sm">Buka Laporan Lengkap →</a>
            </div>
            <p class="text-muted text-sm">
                Lihat rincian lengkap transaksi pelanggan, cetak rekap data keuangan, serta ekspor status penyewaan harian melalui menu <strong>Laporan Transaksi</strong>.
            </p>
        </div>

    </main>
</div>

<footer class="site-footer">
    © <?= date('Y') ?> Tulip.projectt | Sistem Informasi Penyewaan Peralatan Piknik
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
