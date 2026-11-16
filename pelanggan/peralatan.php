<?php
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->query("
    SELECT * FROM peralatan
    WHERE status = 'tersedia'
    ORDER BY nama_peralatan ASC
");
$peralatan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Peralatan Piknik</title>
<link rel="stylesheet" href="../assets/css/pelanggan.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <nav class="nav">
        <a href="dashboard.php">Home</a>
        <a href="peralatan.php" class="active">Peralatan</a>
        <a href="kontak.php">Kontak</a>
        <a href="../auth/login_pelanggan.php" class="btn-login">Login / Sign Up</a>
    </nav>
</header>

<main class="page-content">

<!-- TITLE -->
<section class="page-title">
    <h1>🧺 Peralatan Piknik</h1>
    <p>Pilih peralatan favorit untuk piknik nyaman</p>

<!-- LIST PERALATAN -->
<section class="rekomendasi">
    <div class="rekomendasi-grid">

        <?php foreach ($peralatan as $row): ?>
            <div class="card-piknik">
                <a href="detail_peralatan.php?id=<?= urlencode($row['id_peralatan']) ?>">
                    <img src="../uploads/peralatan/<?= e($row['gambar']) ?>" alt="<?= e($row['nama_peralatan']) ?>">
                    <h4><?= e($row['nama_peralatan']) ?></h4>
                </a>

                <div class="harga">
                    Rp <?= number_format((float)$row['harga_sewa'], 0, ',', '.') ?> / hari
                </div>

                <a href="../auth/login_pelanggan.php" class="btn-login btn-full ">
                    + Keranjang
                </a>
            </div>
        <?php endforeach; ?>

    </div>
</section>
</section>
</main>

<!-- FOOTER -->
<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt | Penyewaan Peralatan Piknik
</footer>

</body>
</html>
