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

// Fitur Ekspor Laporan CSV (Memenuhi Kelengkapan Fitur Minimum A.3)
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
    <title>Owner | Laporan Transaksi</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .img-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
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
        <a href="dashboard.php">🏠 Dashboard</a>
        <a href="data_peralatan.php">🧺 Data Peralatan</a>
        <a href="laporan_transaksi.php" class="active">📑 Data Transaksi</a>
        <a href="data_users.php">👤 Data Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3 style="margin:0;">📑 Laporan Transaksi</h3>
                <a href="?export=csv" class="btn-sm" style="background:#2e7d32; color:#fff; text-decoration:none; padding:8px 16px; border-radius:6px; font-weight:bold; display:inline-flex; align-items:center; gap:6px;">📥 Ekspor CSV</a>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Penyewa</th>
                        <th>Gambar</th>
                        <th>Peralatan</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                foreach ($laporan as $row):
                    $badge = match ($row['status']) {
                        'menunggu'   => 'warning',
                        'diproses'   => 'secondary',
                        'disetujui'  => 'info',
                        'selesai'    => 'success',
                        'dibatalkan' => 'danger',
                        default      => 'secondary'
                    };
                    $gambar = $row['gambar']
                        ? "../uploads/peralatan/" . $row['gambar']
                        : "../assets/img/no-image.png";
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal_transaksi'])) ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td align="center">
                            <img src="<?= e($gambar) ?>" class="img-thumb">
                        </td>
                        <td><?= e($row['nama_peralatan']) ?></td>
                        <td><?= e((string)$row['jumlah']) ?></td>
                        <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                        <td>
                            <span class="badge <?= $badge ?>">
                                <?= ucfirst(e($row['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <a href="detail_transaksi.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn-sm logout">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($laporan)): ?>
                <p><em>Belum ada transaksi recorded.</em></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Owner | Penyewaan Peralatan Piknik
</div>

</body>
</html>
