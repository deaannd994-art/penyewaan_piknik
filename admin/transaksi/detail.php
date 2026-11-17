<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

$id = $_GET['id'] ?? null;
if (!is_valid_uuid($id)) {
    die('400 Bad Request: ID transaksi tidak valid');
}

/* ========================================================
   UPDATE STATUS
======================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    SecurityHelper::requireCsrf();

    $status = $_POST['status'];
    $status_valid = ['menunggu', 'disetujui', 'diproses', 'selesai', 'dibatalkan'];

    if (!in_array($status, $status_valid, true)) {
        die('400 Bad Request: Status tidak valid');
    }

    $stmtUp = $pdo->prepare("UPDATE transaksi SET status = :status WHERE id_transaksi = :id");
    $stmtUp->execute(['status' => $status, 'id' => $id]);

    log_audit($pdo, $_SESSION['id_user'], 'UPDATE_STATUS_TRANSAKSI', 'transaksi', 'success', "ID: {$id} -> {$status}");

    header("Location: index.php");
    exit;
}

/* ========================================================
   DATA TRANSAKSI + USER
======================================================== */
$stmtTx = $pdo->prepare("
    SELECT 
        t.*,
        u.nama,
        u.email,
        u.no_hp
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    WHERE t.id_transaksi = :id
    LIMIT 1
");
$stmtTx->execute(['id' => $id]);
$transaksi = $stmtTx->fetch();

if (!$transaksi) {
    die('404 Not Found: Transaksi tidak ditemukan');
}

/* ========================================================
   DETAIL PERALATAN
======================================================== */
$stmtDetail = $pdo->prepare("
    SELECT 
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM detail_transaksi d
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    WHERE d.id_transaksi = :id
");
$stmtDetail->execute(['id' => $id]);
$details = $stmtDetail->fetchAll();

$st = strtolower($transaksi['status']);
$badge = match ($st) {
    'menunggu'   => 'badge-warning',
    'diproses'   => 'badge-warning',
    'disetujui'  => 'badge-success',
    'selesai'    => 'badge-success',
    'dibatalkan' => 'badge-danger',
    default      => 'badge-neutral'
};

$bukti = $transaksi['bukti_pembayaran']
    ? app_path("uploads/bukti/" . SecurityHelper::e($transaksi['bukti_pembayaran']))
    : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi #<?= SecurityHelper::e(substr($transaksi['id_transaksi'], 0, 8)) ?> - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="nav-container">
        <div class="brand-logo">
            <span class="basket-icon">🧺</span>
            <span>Tulip.projectt</span>
            <span class="brand-badge">Admin Panel</span>
        </div>
        <div class="nav-actions">
            <span class="user-dropdown-btn">🛡️ <?= SecurityHelper::e($_SESSION['nama'] ?? 'Admin') ?></span>
            <a href="<?= app_path('auth/logout.php') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</header>

<div class="dashboard-layout">
    <!-- SIDEBAR -->
    <aside class="dashboard-sidebar">
        <div class="sidebar-title">Menu Utama</div>
        <a href="../dashboard.php" class="sidebar-nav-item">
            <span>🏠</span> Dashboard
        </a>
        <a href="../peralatan/index.php" class="sidebar-nav-item">
            <span>🧺</span> Peralatan
        </a>
        <a href="index.php" class="sidebar-nav-item active">
            <span>💳</span> Transaksi
        </a>
        <a href="../users/index.php" class="sidebar-nav-item">
            <span>👤</span> Data Users
        </a>
        <div class="sidebar-title">Keamanan & Audit</div>
        <a href="../audit_logs.php" class="sidebar-nav-item">
            <span>🛡️</span> Security Audit Logs
        </a>
    </aside>

    <!-- CONTENT -->
    <main class="dashboard-main">
        <div class="mb-3 d-flex justify-between align-center">
            <a href="index.php" class="btn btn-outline btn-sm">← Kembali ke Daftar Transaksi</a>
            <span class="badge <?= $badge ?>">Status: <?= strtoupper(SecurityHelper::e($transaksi['status'])) ?></span>
        </div>

        <div class="story-grid mb-4">
            <!-- DATA TRANSAKSI -->
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📑</span> Rincian Pesanan #<?= SecurityHelper::e(substr($transaksi['id_transaksi'], 0, 8)) ?>
                </h2>

                <div class="detail-specs">
                    <div class="detail-spec-item">
                        <span>Penyewa</span>
                        <strong><?= SecurityHelper::e($transaksi['nama']) ?> (<?= SecurityHelper::e($transaksi['email']) ?>)</strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>No. HP / WA</span>
                        <strong><?= SecurityHelper::e($transaksi['no_hp'] ?: '-') ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Jadwal Sewa</span>
                        <strong><?= SecurityHelper::e(date('d M Y', strtotime($transaksi['tanggal_sewa']))) ?> s/d <?= SecurityHelper::e(date('d M Y', strtotime($transaksi['tanggal_kembali']))) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Total Tagihan</span>
                        <strong class="text-primary"><?= SecurityHelper::rupiah($transaksi['total_harga']) ?></strong>
                    </div>
                    <div class="detail-spec-item">
                        <span>Alamat Pengiriman</span>
                        <div class="text-right"><?= nl2br(SecurityHelper::e($transaksi['alamat'] ?: '-')) ?></div>
                    </div>
                    <div class="detail-spec-item">
                        <span>Catatan Pesanan</span>
                        <div class="text-right"><?= nl2br(SecurityHelper::e($transaksi['catatan'] ?: '-')) ?></div>
                    </div>
                </div>

                <!-- UPDATE STATUS FORM -->
                <div class="mt-4 pt-3" style="border-top:1px solid var(--border-color);">
                    <h3 class="checkout-card-title">
                        <span>⚙️</span> Perbarui Status Pesanan
                    </h3>
                    <form method="POST" class="d-flex gap-2 align-center">
                        <?= SecurityHelper::csrfField() ?>
                        <select name="status" class="form-control flex-1" required>
                            <?php foreach (['menunggu', 'disetujui', 'diproses', 'selesai', 'dibatalkan'] as $st): ?>
                                <option value="<?= $st ?>" <?= $transaksi['status'] === $st ? 'selected' : '' ?>>
                                    <?= ucfirst($st) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            Simpan Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- BUKTI PEMBAYARAN -->
            <div class="checkout-card">
                <h2 class="checkout-card-title">
                    <span>📸</span> Bukti Pembayaran
                </h2>

                <?php if ($bukti): ?>
                    <div class="text-center">
                        <img src="<?= $bukti ?>" class="img-bukti-card" alt="Bukti Transfer">
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p class="text-muted text-sm">Tidak ada lampiran bukti transfer (atau memilih metode COD/Bayar di Tempat).</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PERALATAN DISEWA TABLE -->
        <div class="checkout-card">
            <h2 class="checkout-card-title">
                <span>🧺</span> Item Peralatan yang Disewa
            </h2>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Peralatan</th>
                            <th>Tarif Sewa / Hari</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $d): ?>
                        <tr>
                            <td>
                                <?php if ($d['gambar']): ?>
                                    <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($d['gambar'])) ?>" 
                                         class="img-product-table" alt="Peralatan">
                                <?php endif; ?>
                            </td>
                            <td><strong><?= SecurityHelper::e($d['nama_peralatan']) ?></strong></td>
                            <td><?= SecurityHelper::rupiah($d['harga_sewa']) ?></td>
                            <td class="text-bold"><?= (int)$d['jumlah'] ?> unit</td>
                            <td class="text-bold text-primary"><?= SecurityHelper::rupiah($d['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<footer class="site-footer">
    © <?= date('Y') ?> Tulip.projectt | Sistem Informasi Penyewaan Peralatan Piknik
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
