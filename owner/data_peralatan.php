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
    <title>Owner - Peralatan</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
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
        <a href="data_peralatan.php" class="active">🧺 Data Peralatan</a>
        <a href="laporan_transaksi.php">📑 Data Transaksi</a>
        <a href="data_users.php">👤 Data Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="card">
            <h3>Daftar Peralatan</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Gambar</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($peralatan as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if ($row['gambar']): ?>
                                <img src="../uploads/peralatan/<?= e($row['gambar']) ?>" width="60" style="border-radius:4px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['nama_peralatan']) ?></td>
                        <td><?= nl2br(e($row['deskripsi'])) ?></td>
                        <td>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                        <td><?= e((string)$row['stok']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'tersedia'): ?>
                                <span class="badge success">Tersedia</span>
                            <?php else: ?>
                                <span class="badge danger">Tidak tersedia</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Owner | Penyewaan Peralatan Piknik
</div>

</body>
</html>
