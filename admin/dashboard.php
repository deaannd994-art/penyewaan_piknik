<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

/* =========================
   DATA RINGKAS
========================= */

// Total transaksi
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM transaksi");
$totalTransaksi = (int) ($stmt->fetch()['total'] ?? 0);

// Transaksi selesai
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'selesai'");
$transaksiSelesai = (int) ($stmt->fetch()['total'] ?? 0);

// Pendapatan hari ini
$stmt = $pdo->query("SELECT SUM(total_harga) AS total FROM transaksi WHERE status = 'selesai' AND DATE(created_at) = CURDATE()");
$pendapatanHariIni = (int) ($stmt->fetch()['total'] ?? 0);

// Jenis sewa piknik
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM peralatan");
$jenisPiknik = (int) ($stmt->fetch()['total'] ?? 0);

// Total stok tersedia
$stmt = $pdo->query("SELECT SUM(stok) AS total FROM peralatan WHERE status = 'tersedia'");
$totalStok = (int) ($stmt->fetch()['total'] ?? 0);

/* =========================
   DATA GRAFIK (30 HARI)
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

// Generate tanggal 30 hari terakhir
$labels = [];
$dataPendapatan = [];

for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d M', strtotime($tgl));
    $dataPendapatan[$tgl] = 0;
}

// Isi data dari database
while ($row = $stmtGrafik->fetch()) {
    if (isset($dataPendapatan[$row['tanggal']])) {
        $dataPendapatan[$row['tanggal']] = (int) $row['total'];
    }
}

$pendapatanHarian = array_values($dataPendapatan);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
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
    <div class="logo">Admin | Penyewaan Peralatan Piknik</div>
    <a href="../auth/logout.php" class="btn-sm logout">Logout</a>
</div>

<div class="wrapper">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="dashboard.php" class="active">🏠 Dashboard</a>
        <a href="peralatan/index.php">🧺 Peralatan</a>
        <a href="transaksi/index.php">💳 Transaksi</a>
        <a href="users/index.php">👤 Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <h2 class="dashboard-title">Selamat datang, <?= e($_SESSION['nama'] ?? 'Admin') ?> 👋</h2>

        <div class="dashboard-cards">
            <div class="dashboard-card">
                <h4>🧺 Jenis Sewa Piknik</h4>
                <span><?= e((string)$jenisPiknik) ?></span>
            </div>

            <div class="dashboard-card">
                <h4>📦 Stok Tersedia</h4>
                <span><?= e((string)$totalStok) ?></span>
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
                <h4>📅 Pendapatan Hari Ini</h4>
                <span>Rp <?= number_format($pendapatanHariIni, 0, ',', '.') ?></span>
            </div>
        </div>

        <!-- GRAFIK -->
        <div class="dashboard-chart">
            <h3>📊 Pendapatan Harian (30 Hari Terakhir)</h3>
            <canvas id="chartHarian"></canvas>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Admin | Penyewaan Peralatan Piknik
</div>

<script>
const labelsHarian = <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const dataHarian   = <?= json_encode($pendapatanHarian) ?>;

new Chart(document.getElementById('chartHarian'), {
    type: 'line',
    data: {
        labels: labelsHarian,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: dataHarian,
            tension: 0.3,
            fill: true,
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
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
