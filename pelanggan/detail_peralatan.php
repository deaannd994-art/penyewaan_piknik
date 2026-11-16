<?php
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? '';

if (!is_valid_uuid($id)) {
    http_response_code(400);
    die("ID peralatan tidak valid.");
}

$stmt = $pdo->prepare("SELECT * FROM peralatan WHERE id_peralatan = :id LIMIT 1");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch();

if (!$data) {
    http_response_code(404);
    echo "Peralatan tidak ditemukan";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title><?= e($data['nama_peralatan']) ?></title>
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
    <div class="detail-container">

        <section class="detail-peralatan">

            <img src="../uploads/peralatan/<?= e($data['gambar']) ?>" alt="<?= e($data['nama_peralatan']) ?>" class="detail-img">

            <div class="detail-info">

                <h1><?= e($data['nama_peralatan']) ?></h1>

                <p class="harga">
                    Rp <?= number_format((float)$data['harga_sewa'], 0, ',', '.') ?> / hari
                </p>

                <p class="deskripsi">
                    <?= nl2br(e($data['deskripsi'])) ?>
                </p>

                <div class="detail-bottom">

                    <div class="detail-actions">
                        <a href="../auth/login_pelanggan.php" class="btn-login">
                             + Keranjang                            
                        </a>

                        <a href="../auth/login_pelanggan.php" class="btn-login">
                            Sewa Sekarang
                        </a>
                    </div>

                    <a href="peralatan.php" class="btn-kembali">
                        ← Kembali
                    </a>

                </div>
            </div>

        </section>

    </div>
</main>

<footer class="footer">
    © <?= date('Y') ?> Tulip.projectt
</footer>

</body>
</html>
