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
    <title><?= SecurityHelper::e($data['nama_peralatan']) ?> - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<!-- NAVBAR -->
<header class="site-header">
    <div class="nav-container">
        <a href="dashboard.php" class="brand-logo">
            <span class="basket-icon">🧺</span>
            <span>Tulip.projectt</span>
        </a>

        <button class="nav-toggle" aria-label="Toggle Menu" aria-expanded="false">
            ☰
        </button>

        <nav class="nav-menu">
            <a href="dashboard.php" class="nav-link">Home</a>
            <a href="peralatan.php" class="nav-link active">Peralatan</a>
            <a href="kontak.php" class="nav-link">Kontak & Ketentuan</a>
            <div class="nav-actions">
                <a href="<?= app_path('auth/login_pelanggan.php') ?>" class="btn btn-outline btn-sm">Login</a>
                <a href="<?= app_path('auth/register.php') ?>" class="btn btn-primary btn-sm">Daftar Akun</a>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">

        <div class="mb-3">
            <a href="peralatan.php" class="btn btn-outline btn-sm">← Kembali ke Katalog</a>
        </div>

        <div class="detail-layout">
            <div class="detail-gallery">
                <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($data['gambar'])) ?>" 
                     alt="<?= SecurityHelper::e($data['nama_peralatan']) ?>">
            </div>

            <div class="detail-info">
                <div class="mb-2">
                    <?php if ($data['status'] === 'tersedia' && (int)$data['stok'] > 0): ?>
                        <span class="badge badge-success">Stok Tersedia (<?= (int)$data['stok'] ?> Unit)</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Stok Sedang Habis</span>
                    <?php endif; ?>
                </div>

                <h1 class="detail-title"><?= SecurityHelper::e($data['nama_peralatan']) ?></h1>
                <div class="detail-price"><?= SecurityHelper::rupiah($data['harga_sewa']) ?> <span class="text-muted text-sm">/ hari</span></div>

                <div class="card-desc mb-3">
                    <?= nl2br(SecurityHelper::e($data['deskripsi'])) ?>
                </div>

                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>Kategori Paket</span>
                        <strong>Set Piknik Lengkap</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Kondisi Peralatan</span>
                        <strong>Bersih, Higienis & Siap Pakai</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Durasi Minimal Sewa</span>
                        <strong>1 Hari (24 Jam)</strong>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <?php 
                    $waHargaFormatted = number_format((float)$data['harga_sewa'], 0, ',', '.');
                    $waMessage = "Halo Admin Piknik, saya ingin tanya-tanya atau menyewa paket *" . $data['nama_peralatan'] . "* (Rp " . $waHargaFormatted . "/hari). Apakah masih tersedia untuk tanggal tertentu?";
                    $waUrl = whatsapp_url($waMessage);
                    ?>
                    <a href="<?= SecurityHelper::e($waUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp btn-lg btn-full" title="Tanya Sewa via WhatsApp">
                        💬 Tanya Sewa via WhatsApp
                    </a>
                    <a href="<?= app_path('auth/login_pelanggan.php') ?>" class="btn btn-primary btn-lg btn-full">
                        🧺 Login untuk Menyewa
                    </a>
                </div>
            </div>
        </div>

    </div>
</main>

<footer class="site-footer">
    <div class="container">
        © <?= date('Y') ?> 🧺 <strong>Tulip.projectt</strong> | Sistem Informasi Penyewaan Peralatan Piknik
    </div>
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
