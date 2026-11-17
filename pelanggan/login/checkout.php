<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['pelanggan'], app_path('auth/login_pelanggan.php'));

$nama    = $_SESSION['nama'] ?? 'Pelanggan';
$id_user = $_SESSION['id_user'];

$items = $_POST['id_keranjang'] ?? [];

// Direct checkout dari detail atau parameter GET
if (isset($_GET['id'])) {
    $idPeralatan = $_GET['id'];
    if (is_valid_uuid($idPeralatan)) {
        $stmtCek = $pdo->prepare("SELECT id_keranjang FROM keranjang WHERE id_user = :id_user AND id_peralatan = :id_peralatan LIMIT 1");
        $stmtCek->execute(['id_user' => $id_user, 'id_peralatan' => $idPeralatan]);
        $row = $stmtCek->fetch();

        if ($row) {
            $items = [$row['id_keranjang']];
        } else {
            $newCartId = generate_uuid();
            $ins = $pdo->prepare("INSERT INTO keranjang (id_keranjang, id_user, id_peralatan, jumlah, created_at) VALUES (:id_k, :id_u, :id_p, 1, NOW())");
            $ins->execute([
                'id_k' => $newCartId,
                'id_u' => $id_user,
                'id_p' => $idPeralatan
            ]);
            $items = [$newCartId];
        }
    }
}

if (!is_array($items)) {
    $items = strpos($items, ',') !== false ? explode(',', $items) : [$items];
}

$validItems = [];
foreach ($items as $item) {
    $itemStr = trim((string)$item);
    if (is_valid_uuid($itemStr)) {
        $validItems[] = $itemStr;
    }
}

if (empty($validItems)) {
    header("Location: keranjang.php");
    exit;
}

$placeholders = implode(',', array_fill(0, count($validItems), '?'));
$sql = "
    SELECT k.id_keranjang, k.jumlah, p.nama_peralatan, p.harga_sewa, p.gambar, p.stok, p.status
    FROM keranjang k
    JOIN peralatan p ON k.id_peralatan = p.id_peralatan
    WHERE k.id_user = ? AND k.id_keranjang IN ($placeholders)
";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge([$id_user], $validItems));
$cartList = $stmt->fetchAll();

if (empty($cartList)) {
    header("Location: keranjang.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout Sewa - Tulip.projectt</title>
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
            <a href="peralatan.php" class="nav-link">Peralatan</a>
            <a href="keranjang.php" class="nav-link active">🛒 Keranjang</a>
            <a href="riwayat.php" class="nav-link">📜 Riwayat</a>
            <a href="kontak.php" class="nav-link">Kontak</a>

            <div class="user-dropdown">
                <button type="button" class="user-dropdown-btn">
                    <span>👤</span>
                    <span><?= SecurityHelper::e($nama) ?></span>
                    <span>▾</span>
                </button>
                <div class="user-dropdown-menu">
                    <a href="akun_saya.php" class="user-dropdown-item">⚙️ Akun Saya</a>
                    <a href="riwayat.php" class="user-dropdown-item">📜 Riwayat Sewa</a>
                    <div class="user-dropdown-divider"></div>
                    <a href="<?= app_path('auth/logout_pelanggan.php') ?>" class="user-dropdown-item text-danger">🚪 Logout</a>
                </div>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">

        <div class="page-header">
            <h1 class="page-title">💳 Checkout & Konfirmasi Sewa</h1>
            <p class="page-subtitle">Lengkapi formulir jadwal sewa, alamat pengiriman, dan metode pembayaran.</p>
        </div>

        <form action="proses_sewa.php" method="POST" enctype="multipart/form-data">
            <?= SecurityHelper::csrfField() ?>

            <?php foreach ($validItems as $idk): ?>
                <input type="hidden" name="id_keranjang[]" value="<?= SecurityHelper::e($idk) ?>">
            <?php endforeach; ?>

            <!-- 2-COLUMN CHECKOUT GRID -->
            <div class="checkout-grid">

                <!-- LEFT COLUMN: Item Review & Form Data -->
                <div>
                    <!-- ORDER REVIEW -->
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">
                            <span>📦</span> Rincian Item Sewa
                        </h2>

                        <ul class="order-review-list">
                            <?php 
                            $subtotalPerHari = 0;
                            foreach ($cartList as $row): 
                                $itemSubtotal = (float)$row['harga_sewa'] * (int)$row['jumlah'];
                                $subtotalPerHari += $itemSubtotal;
                            ?>
                            <li class="order-review-item">
                                <div class="order-review-thumb">
                                    <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                                         alt="<?= SecurityHelper::e($row['nama_peralatan']) ?>">
                                </div>
                                <div class="order-review-details">
                                    <div class="order-review-title"><?= SecurityHelper::e($row['nama_peralatan']) ?></div>
                                    <div class="order-review-meta">Jumlah: <?= (int)$row['jumlah'] ?> unit × <?= SecurityHelper::rupiah($row['harga_sewa']) ?>/hari</div>
                                </div>
                                <div class="order-review-price">
                                    <?= SecurityHelper::rupiah($itemSubtotal) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- FORM DATA PENYEWAAN -->
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">
                            <span>📅</span> Jadwal & Informasi Pengiriman
                        </h2>

                        <div class="d-grid checkout-dates-grid">
                            <div class="form-group">
                                <label class="form-label" for="tglSewa">Tanggal Mulai Sewa</label>
                                <input type="date" id="tglSewa" name="tanggal_sewa" class="form-control" 
                                       required min="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tglKembali">Tanggal Pengembalian</label>
                                <input type="date" id="tglKembali" name="tanggal_kembali" class="form-control" 
                                       required min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="checkoutAlamat">Alamat Pengiriman / Pengambilan</label>
                            <textarea id="checkoutAlamat" name="alamat" class="form-control" rows="3" 
                                      required placeholder="Alamat lengkap tujuan pengiriman atau spot piknik..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="checkoutCatatan">Catatan Khusus (Opsional)</label>
                            <textarea id="checkoutCatatan" name="catatan" class="form-control" rows="2" 
                                      placeholder="Contoh: Titip di pos satpam atau waktu pengiriman pukul 09.00 WIB..."></textarea>
                        </div>

                        <h2 class="checkout-card-title mt-4">
                            <span>💳</span> Metode Pembayaran
                        </h2>

                        <div class="form-group">
                            <label class="form-label" for="metodePembayaran">Pilih Pembayaran</label>
                            <select name="metode_pembayaran" id="metodePembayaran" class="form-control" required>
                                <option value="">-- Pilih Rekening / Metode --</option>
                                <option value="Transfer Bank">Transfer Bank (BCA / Mandiri / BRI)</option>
                                <option value="E-Wallet">E-Wallet (GoPay / OVO / ShopeePay / Dana)</option>
                                <option value="Bayar di Tempat">Bayar di Tempat (COD Saat Serah Terima)</option>
                            </select>
                        </div>

                        <!-- UPLOAD BUKTI WITH REAL-TIME IMAGE PREVIEW -->
                        <div class="form-group" id="buktiBox">
                            <label class="form-label" for="buktiInput">
                                Unggah Bukti Transfer (JPG/PNG, Maks. 2MB)
                            </label>
                            <input type="file" id="buktiInput" name="bukti_pembayaran" class="form-control" 
                                   accept=".jpg,.jpeg,.png" data-preview="buktiPreviewContainer">
                            
                            <!-- Container Image Preview -->
                            <div class="image-preview-wrapper" id="buktiPreviewContainer"></div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Sticky Order Summary -->
                <div class="sticky-summary">
                    <div class="checkout-card">
                        <h2 class="checkout-card-title">
                            <span>🧾</span> Ringkasan Tagihan
                        </h2>

                        <input type="hidden" id="subtotalPerHari" value="<?= (int)$subtotalPerHari ?>">
                        <input type="hidden" name="total_harga" id="totalFinal" value="<?= (int)$subtotalPerHari ?>">
                        <input type="hidden" name="durasi" id="durasiHari" value="1">

                        <div class="summary-row">
                            <span>Biaya Sewa / Hari:</span>
                            <strong class="text-dark"><?= SecurityHelper::rupiah($subtotalPerHari) ?></strong>
                        </div>

                        <div class="summary-row">
                            <span>Durasi Sewa:</span>
                            <span id="checkoutDurasiInfo" class="text-bold text-muted">Pilih tanggal di formulir</span>
                        </div>

                        <div class="summary-row">
                            <span>Biaya Layanan:</span>
                            <span class="text-success text-bold">Gratis (Rp 0)</span>
                        </div>

                        <div class="summary-row total-row">
                            <span>Total Biaya:</span>
                            <span id="checkoutTotalHarga"><?= SecurityHelper::rupiah($subtotalPerHari) ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full btn-lg mt-4">
                            🔒 Konfirmasi & Sewa Sekarang
                        </button>
                        <p class="text-center text-xs text-muted mt-2">
                            Pembayaran dienkripsi aman & tercatat di sistem audit Tulip.projectt
                        </p>
                    </div>
                </div>

            </div>
        </form>

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
