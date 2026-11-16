<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$id_user = $_SESSION['id_user'];
$nama    = $_SESSION['nama'] ?? 'Pelanggan';

$stmt = $pdo->prepare("
    SELECT 
        t.id_transaksi,
        t.status,
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE t.id_user = :id_user
    ORDER BY t.created_at DESC
");
$stmt->execute(['id_user' => $id_user]);
$riwayat = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Sewa</title>
    <link rel="stylesheet" href="../../assets/css/pelanggan.css">
    <link rel="stylesheet" href="../../assets/css/transaksi_sewa.css">
</head>
<body>

<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php">Home</a>
        <a href="peralatan.php">Peralatan</a>
        <a href="keranjang.php">Keranjang</a>
        <a href="riwayat.php" class="active">Riwayat</a>
        <a href="kontak.php">Kontak</a>

        <div class="akun-wrapper">
            <button class="btn-login">
                <?= e($nama) ?> ▾
            </button>

            <div class="akun-menu">
                <a href="akun_saya.php">Akun Saya</a>
                <a href="../../auth/logout_pelanggan.php">Logout</a>
            </div>
        </div>
    </nav>
</header>

<section class="page-title">
    <h1>📜 Riwayat Sewa</h1>
    <p>Ringkasan penyewaan peralatan</p>
</section>

<div class="riwayat-wrapper">
    <div class="riwayat-card">

        <?php if (empty($riwayat)): ?>
            <div class="riwayat-kosong">
                <p>Belum ada riwayat sewa</p>
                <a href="peralatan.php" class="btn-login" style="margin-top: 15px; display: inline-block;">Sewa Sekarang</a>
            </div>
        <?php else: ?>

        <table class="riwayat-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Peralatan</th>
                    <th>Harga / Hari</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>

            <?php 
             $no = 1;
             foreach ($riwayat as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td style="text-align:left">
                        <div style="display:flex; gap:10px; align-items:center;">
                            <img src="../../uploads/peralatan/<?= e($row['gambar']) ?>"
                                 alt="<?= e($row['nama_peralatan']) ?>"
                                 style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                            <strong><?= e($row['nama_peralatan']) ?></strong>
                        </div>
                    </td>
                    <td>Rp <?= number_format((float)$row['harga_sewa'], 0, ',', '.') ?></td>
                    <td><?= (int)$row['jumlah'] ?></td>
                    <td>Rp <?= number_format((float)$row['subtotal'], 0, ',', '.') ?></td>
                    <td>
                        <span class="status <?= strtolower(e($row['status'])) ?>">
                            <?= ucfirst(e($row['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <a href="detail_sewa.php?id=<?= urlencode($row['id_transaksi']) ?>"
                           class="btn-login">
                            Detail
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt | Penyewaan Peralatan Piknik
</footer>

</body>
</html>
