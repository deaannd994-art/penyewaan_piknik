<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

$id_user = $_GET['id'] ?? null;
if (!is_valid_uuid($id_user)) {
    die('400 Bad Request: ID user tidak valid');
}

/* DATA USER */
$stmtUser = $pdo->prepare("
    SELECT id_user, nama, email, no_hp, alamat, created_at
    FROM users
    WHERE id_user = :id AND role = 'pelanggan'
    LIMIT 1
");
$stmtUser->execute(['id' => $id_user]);
$user = $stmtUser->fetch();

if (!$user) {
    die('404 Not Found: User tidak ditemukan');
}

/* RIWAYAT TRANSAKSI USER */
$stmtTx = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        t.created_at,
        t.status,
        p.nama_peralatan,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE t.id_user = :id
    ORDER BY t.created_at DESC
");
$stmtTx->execute(['id' => $id_user]);
$riwayat = $stmtTx->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin | Detail User</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
    <div class="logo">Admin | Penyewaan Peralatan Piknik</div>
    <a href="../../auth/logout.php" class="btn-sm logout">Logout</a>
</div>

<div class="wrapper">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <a href="../dashboard.php">🏠 Dashboard</a>
        <a href="../peralatan/index.php">🧺 Peralatan</a>
        <a href="../transaksi/index.php">💳 Transaksi</a>
        <a href="index.php" class="active">👤 Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <h2>Detail Pelanggan</h2>

        <!-- INFO USER -->
        <div class="card">
            <p><strong>Nama:</strong> <?= e($user['nama']) ?></p>
            <p><strong>Email:</strong> <?= e($user['email']) ?></p>
            <p><strong>No HP:</strong> <?= e($user['no_hp'] ?? '-') ?></p>
            <p><strong>Alamat:</strong> <?= e($user['alamat'] ?? '-') ?></p>
            <p><strong>Tanggal Daftar:</strong> <?= date('d-m-Y', strtotime($user['created_at'])) ?></p>
            <a href="index.php" class="btn-sm" style="display:inline-block; margin-top:10px; text-decoration:none;">Kembali</a>
        </div>

        <!-- RIWAYAT TRANSAKSI -->
        <div class="card">
            <h3>Riwayat Pemesanan</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Gambar</th>
                        <th>Peralatan</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                foreach ($riwayat as $row):
                    $badge = match ($row['status']) {
                        'menunggu'   => 'warning',
                        'diproses'   => 'secondary',
                        'disetujui'  => 'info',
                        'selesai'    => 'success',
                        'dibatalkan' => 'danger',
                        default      => 'secondary'
                    };
                    $gambar = $row['gambar']
                        ? "../../uploads/peralatan/" . $row['gambar']
                        : "../../assets/img/no-image.png";
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
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
                            <a href="../transaksi/detail.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn-sm logout">
                                Detail
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($riwayat)): ?>
                <p><em>User ini belum pernah melakukan transaksi.</em></p>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Admin | Penyewaan Peralatan Piknik
</div>

</body>
</html>
