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
    require_csrf();

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
        u.email
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

/* BADGE STATUS */
$badge = match ($transaksi['status']) {
    'menunggu'   => 'warning',
    'diproses'   => 'secondary',
    'disetujui'  => 'info',
    'selesai'    => 'success',
    'dibatalkan' => 'danger',
    default      => 'secondary'
};

/* PATH BUKTI PEMBAYARAN */
$bukti = $transaksi['bukti_pembayaran']
    ? "../../uploads/bukti/" . $transaksi['bukti_pembayaran']
    : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .img-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
        .img-bukti {
            max-width: 300px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <div class="logo">Admin | Detail Transaksi</div>
    <a href="../../auth/logout.php" class="btn-sm logout">Logout</a>
</div>

<div class="wrapper">
    <div class="sidebar">
        <a href="../dashboard.php">🏠 Dashboard</a>
        <a href="../peralatan/index.php">🧺 Peralatan</a>
        <a href="index.php" class="active">💳 Transaksi</a>
        <a href="../users/index.php">👤 Users</a>
    </div>

    <div class="content">

        <div class="card">
            <h2>Detail Transaksi #<?= e(substr($transaksi['id_transaksi'], 0, 8)) ?></h2>
            <p><strong>Nama Penyewa:</strong> <?= e($transaksi['nama']) ?> (<?= e($transaksi['email']) ?>)</p>
            <p><strong>Tanggal Sewa:</strong> <?= e($transaksi['tanggal_sewa']) ?></p>
            <p><strong>Tanggal Kembali:</strong> <?= e($transaksi['tanggal_kembali']) ?></p>
            <p><strong>Status Saat Ini:</strong> <span class="badge <?= $badge ?>"><?= ucfirst(e($transaksi['status'])) ?></span></p>
            <p><strong>Total Pembayaran:</strong> Rp <?= number_format($transaksi['total_harga'], 0, ',', '.') ?></p>
            <p><strong>Alamat:</strong> <?= nl2br(e($transaksi['alamat'] ?? '-')) ?></p>
            <p><strong>Catatan:</strong> <?= nl2br(e($transaksi['catatan'] ?? '-')) ?></p>

            <hr>
            <h3>Peralatan yang Disewa</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Peralatan</th>
                        <th>Harga Sewa/Hari</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($details as $d): ?>
                    <tr>
                        <td>
                            <?php if ($d['gambar']): ?>
                                <img src="../../uploads/peralatan/<?= e($d['gambar']) ?>" class="img-thumb">
                            <?php endif; ?>
                        </td>
                        <td><?= e($d['nama_peralatan']) ?></td>
                        <td>Rp <?= number_format($d['harga_sewa'], 0, ',', '.') ?></td>
                        <td><?= e((string)$d['jumlah']) ?></td>
                        <td>Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($bukti): ?>
                <hr>
                <h3>Bukti Pembayaran</h3>
                <img src="<?= e($bukti) ?>" class="img-bukti" alt="Bukti Pembayaran">
            <?php endif; ?>

            <hr>
            <h3>Perbarui Status Transaksi</h3>
            <form method="POST">
                <?= csrf_field() ?>
                <select name="status" required>
                    <?php foreach (['menunggu', 'disetujui', 'diproses', 'selesai', 'dibatalkan'] as $st): ?>
                        <option value="<?= $st ?>" <?= $transaksi['status'] === $st ? 'selected' : '' ?>>
                            <?= ucfirst($st) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn">Simpan Perubahan</button>
                <a href="index.php" class="btn-sm" style="display:inline-block; text-decoration:none; padding:10px 15px;">Kembali</a>
            </form>
        </div>

    </div>
</div>

<div class="footer">
    © <?= date('Y') ?> Admin | Penyewaan Peralatan Piknik
</div>

</body>
</html>
