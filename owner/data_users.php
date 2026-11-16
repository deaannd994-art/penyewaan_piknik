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
    <title>Owner | Data Users</title>
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
        <a href="data_peralatan.php">🧺 Data Peralatan</a>
        <a href="laporan_transaksi.php">📑 Data Transaksi</a>
        <a href="data_users.php" class="active">👤 Data Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="card">
            <h3>📋 Data Pelanggan</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th>Alamat</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($users as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['no_hp'] ?? '-') ?></td>
                        <td><?= e($row['alamat'] ?? '-') ?></td>
                        <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="detail_users.php?id=<?= urlencode($row['id_user']) ?>" class="btn-sm logout">
                                Detail
                            </a>
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
