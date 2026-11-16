<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['owner'], app_path('auth/login_admin.php'));

// Total transaksi
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM transaksi");
$totalTransaksi = (int) ($stmt->fetch()['total'] ?? 0);

// Transaksi selesai
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'selesai'");
$transaksiSelesai = (int) ($stmt->fetch()['total'] ?? 0);

// Total pendapatan
$stmt = $pdo->query("SELECT SUM(total_harga) AS total FROM transaksi WHERE status = 'selesai'");
$totalPendapatan = (int) ($stmt->fetch()['total'] ?? 0);

// Jenis sewa piknik
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM peralatan");
$jenisPiknik = (int) ($stmt->fetch()['total'] ?? 0);

// Stok tersedia
$stmt = $pdo->query("SELECT SUM(stok) AS total FROM peralatan WHERE status = 'tersedia'");
$stokPiknik = (int) ($stmt->fetch()['total'] ?? 0);

// Pendapatan hari ini
$stmt = $pdo->query("SELECT SUM(total_harga) AS total FROM transaksi WHERE status = 'selesai' AND DATE(created_at) = CURDATE()");
$pendapatanHariIni = (int) ($stmt->fetch()['total'] ?? 0);

/* DATA GRAFIK PENDAPATAN 12 BULAN TERAKHIR */
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

// Generate 12 bulan terakhir secara dinamis
$bulan = [];
$dataPendapatan = [];

for ($i = 11; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $key  = date('Y-m', $time);
    $bulan[] = date('M Y', $time);
    $dataPendapatan[$key] = 0;
}

while ($row = $stmtGrafik->fetch()) {
    if (isset($dataPendapatan[$row['bulan']])) {
        $dataPendapatan[$row['bulan']] = (int) $row['total'];
    }
}

$pendapatan = array_values($dataPendapatan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Owner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-chart {
            margin-top: 40px;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <div class="logo">Owner | Penyewaan Peralatan Piknik</div>
    <a href="../auth/logout.php" class="btn-sm logout">Logout</a>
</div>

<div class="wrapper">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="data_peralatan.php">🧺 Data Peralatan</a>
        <a href="laporan_transaksi.php">📑 Data Transaksi</a>
        <a href="data_users.php">👤 Data Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <h2 class="dashboard-title">Selamat datang, <?= e($_SESSION['nama'] ?? 'Owner') ?> 👋</h2>

        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h4>🧺 Jenis Sewa Piknik</h4>
                <span><?= e((string)$jenisPiknik) ?></span>
            </div>

            <div class="dashboard-card">
                <h4>📦 Stok Tersedia</h4>
                <span><?= e((string)$stokPiknik) ?></span>
            </div>

            <div class="dashboard-card">
                <h4>💳 Total Transaksi</h4>
                <span><?= e((string)$totalTransaksi) ?></span>
            </div>

            <div class="dashboard-card">
                <h4>✔ Transaksi Selesai</h4>
                <span><?= e((string)$transaksiSelesai) ?></span>
            </div>

            <div class="dashboard-card">
                <h4>💰 Total Pendapatan</h4>
                <span>Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></span>
            </div>

            <div class="dashboard-card">
                <h4>📅 Pendapatan Hari Ini</h4>
                <span>Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- GRAFIK -->
        <div class="dashboard-chart">
            <h3>📊 Tren Pendapatan Bulanan (12 Bulan Terakhir)</h3>
            <canvas id="chartPendapatan"></canvas>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Owner | Penyewaan Peralatan Piknik
</div>

<script>
const labelsPendapatan = <?= json_encode($bulan, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const dataPendapatan = <?= json_encode($pendapatan) ?>;

new Chart(document.getElementById('chartPendapatan'), {
    type: 'bar',
    data: {
        labels: labelsPendapatan,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: dataPendapatan,
            backgroundColor: '#4e73df',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                ticks: {
                    callback: value => 'Rp ' + value.toLocaleString('id-ID')
                }
            }
        }
    }
});
</script>

</body>
</html>
