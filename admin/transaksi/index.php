<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

$error = '';

/* ========================================================
   HAPUS TRANSAKSI
======================================================== */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $csrf = $_GET['csrf'] ?? '';

    if (SecurityHelper::verifyCsrf($csrf) && is_valid_uuid($id)) {
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id_transaksi = :id");
        $stmt->execute(['id' => $id]);

        log_audit($pdo, $_SESSION['id_user'], 'DELETE_TRANSAKSI', 'transaksi', 'success', "ID: {$id}");
    }
    header("Location: index.php");
    exit;
}

/* ========================================================
   SIMPAN / UPDATE
======================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['simpan']) || isset($_POST['update']))) {
    SecurityHelper::requireCsrf();

    $id_user         = $_POST['id_user'] ?? '';
    $id_peralatan    = $_POST['id_peralatan'] ?? '';
    $jumlah          = filter_var($_POST['jumlah'] ?? 0, FILTER_VALIDATE_INT);
    $tanggal_sewa    = $_POST['tanggal_sewa'] ?? '';
    $tanggal_kembali = $_POST['tanggal_kembali'] ?? '';
    $status          = $_POST['status'] ?? 'menunggu';

    $status_valid = ['menunggu','diproses','disetujui','selesai','dibatalkan'];
    if (!in_array($status, $status_valid, true)) {
        $status = 'menunggu';
    }

    if (!is_valid_uuid($id_user) || !is_valid_uuid($id_peralatan) || $jumlah <= 0 || empty($tanggal_sewa) || empty($tanggal_kembali)) {
        $error = "Data transaksi tidak valid!";
    } else {
        $stmtHarga = $pdo->prepare("SELECT harga_sewa FROM peralatan WHERE id_peralatan = :id LIMIT 1");
        $stmtHarga->execute(['id' => $id_peralatan]);
        $rowHarga = $stmtHarga->fetch();

        if (!$rowHarga) {
            $error = "Peralatan tidak ditemukan!";
        } else {
            $harga = (int) $rowHarga['harga_sewa'];
            $diffHari = (strtotime($tanggal_kembali) - strtotime($tanggal_sewa)) / 86400 + 1;
            $hari = max(1, (int)$diffHari);
            $subtotal = $harga * $jumlah * $hari;

            /* SIMPAN BARU (UUID v4) */
            if (isset($_POST['simpan'])) {
                $id_transaksi = generate_uuid();
                $id_detail    = generate_uuid();

                $pdo->beginTransaction();
                try {
                    $stmtTx = $pdo->prepare("
                        INSERT INTO transaksi (id_transaksi, id_user, tanggal_sewa, tanggal_kembali, total_harga, status, created_at)
                        VALUES (:id_tx, :id_user, :tgl_sewa, :tgl_kembali, :total, :status, NOW())
                    ");
                    $stmtTx->execute([
                        'id_tx'       => $id_transaksi,
                        'id_user'     => $id_user,
                        'tgl_sewa'    => $tanggal_sewa,
                        'tgl_kembali' => $tanggal_kembali,
                        'total'       => $subtotal,
                        'status'      => $status
                    ]);

                    $stmtDt = $pdo->prepare("
                        INSERT INTO detail_transaksi (id_detail, id_transaksi, id_peralatan, jumlah, subtotal)
                        VALUES (:id_detail, :id_tx, :id_peralatan, :jumlah, :subtotal)
                    ");
                    $stmtDt->execute([
                        'id_detail'    => $id_detail,
                        'id_tx'        => $id_transaksi,
                        'id_peralatan' => $id_peralatan,
                        'jumlah'       => $jumlah,
                        'subtotal'     => $subtotal
                    ]);

                    $pdo->commit();
                    log_audit($pdo, $_SESSION['id_user'], 'CREATE_TRANSAKSI', 'transaksi', 'success', "ID: {$id_transaksi}");

                    header("Location: index.php");
                    exit;
                } catch (\Throwable $e) {
                    $pdo->rollBack();
                    $error = "Gagal menyimpan transaksi: " . $e->getMessage();
                }
            }

            /* UPDATE DATA */
            if (isset($_POST['update'])) {
                $id_transaksi = $_POST['id_transaksi'] ?? '';

                if (!is_valid_uuid($id_transaksi)) {
                    $error = "ID transaksi tidak valid!";
                } else {
                    $pdo->beginTransaction();
                    try {
                        $stmtUpTx = $pdo->prepare("
                            UPDATE transaksi SET
                                id_user         = :id_user,
                                tanggal_sewa    = :tgl_sewa,
                                tanggal_kembali = :tgl_kembali,
                                total_harga     = :total,
                                status          = :status
                            WHERE id_transaksi  = :id_tx
                        ");
                        $stmtUpTx->execute([
                            'id_user'     => $id_user,
                            'tgl_sewa'    => $tanggal_sewa,
                            'tgl_kembali' => $tanggal_kembali,
                            'total'       => $subtotal,
                            'status'      => $status,
                            'id_tx'       => $id_transaksi
                        ]);

                        $stmtUpDt = $pdo->prepare("
                            UPDATE detail_transaksi SET
                                id_peralatan = :id_peralatan,
                                jumlah       = :jumlah,
                                subtotal     = :subtotal
                            WHERE id_transaksi = :id_tx
                        ");
                        $stmtUpDt->execute([
                            'id_peralatan' => $id_peralatan,
                            'jumlah'       => $jumlah,
                            'subtotal'     => $subtotal,
                            'id_tx'        => $id_transaksi
                        ]);

                        $pdo->commit();
                        log_audit($pdo, $_SESSION['id_user'], 'UPDATE_TRANSAKSI', 'transaksi', 'success', "ID: {$id_transaksi}");

                        header("Location: index.php");
                        exit;
                    } catch (\Throwable $e) {
                        $pdo->rollBack();
                        $error = "Gagal memperbarui transaksi: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

/* ========================================================
   EDIT DATA
======================================================== */
$edit = null;
if (isset($_GET['edit']) && is_valid_uuid($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("
        SELECT t.*, d.id_peralatan, d.jumlah
        FROM transaksi t
        LEFT JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
        WHERE t.id_transaksi = :id
        LIMIT 1
    ");
    $stmtEdit->execute(['id' => $_GET['edit']]);
    $edit = $stmtEdit->fetch();
}

/* ========================================================
   DROPDOWN & LIST DATA
======================================================== */
$stmtUsers = $pdo->query("SELECT id_user, nama FROM users WHERE role = 'pelanggan' ORDER BY nama ASC");
$users = $stmtUsers->fetchAll();

$stmtPeralatan = $pdo->query("SELECT id_peralatan, nama_peralatan, harga_sewa FROM peralatan WHERE status = 'tersedia' ORDER BY nama_peralatan ASC");
$peralatan = $stmtPeralatan->fetchAll();

$stmtData = $pdo->query("
    SELECT
        t.id_transaksi,
        t.status,
        u.nama,
        p.nama_peralatan,
        p.harga_sewa,
        p.gambar,
        d.jumlah,
        d.subtotal
    FROM transaksi t
    JOIN users u ON t.id_user = u.id_user
    JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    JOIN peralatan p ON d.id_peralatan = p.id_peralatan
    ORDER BY t.created_at DESC
");
$data = $stmtData->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Transaksi - Admin Tulip.projectt</title>
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
        <div class="page-header">
            <h1 class="page-title">💳 Manajemen Transaksi Penyewaan</h1>
            <p class="page-subtitle">Kelola pemesanan, perbarui status pesanan, dan input transaksi manual.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <span>⚠️</span>
                <div><?= SecurityHelper::e($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- FORM TRANSAKSI -->
        <div class="checkout-card mb-4">
            <h2 class="checkout-card-title">
                <span>📝</span> <?= $edit ? 'Edit Transaksi Penyewaan' : 'Tambah Transaksi Manual Baru' ?>
            </h2>

            <form method="POST" id="formTransaksi">
                <?= SecurityHelper::csrfField() ?>

                <?php if ($edit): ?>
                    <input type="hidden" name="id_transaksi" value="<?= SecurityHelper::e($edit['id_transaksi']) ?>">
                <?php endif; ?>

                <div class="filter-row mb-3">
                    <div class="form-group flex-1">
                        <label class="form-label">Pelanggan</label>
                        <select name="id_user" class="form-control" required>
                            <option value="">-- Pilih Pelanggan --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= SecurityHelper::e($u['id_user']) ?>"
                                    <?= ($edit['id_user'] ?? '') === $u['id_user'] ? 'selected' : '' ?>>
                                    <?= SecurityHelper::e($u['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Peralatan</label>
                        <select name="id_peralatan" id="id_peralatan" class="form-control" required>
                            <option value="">-- Pilih Peralatan --</option>
                            <?php foreach ($peralatan as $p): ?>
                                <option value="<?= SecurityHelper::e($p['id_peralatan']) ?>"
                                    data-harga="<?= (int)$p['harga_sewa'] ?>"
                                    <?= ($edit['id_peralatan'] ?? '') === $p['id_peralatan'] ? 'selected' : '' ?>>
                                    <?= SecurityHelper::e($p['nama_peralatan']) ?> (<?= SecurityHelper::rupiah($p['harga_sewa']) ?>/hari)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="min-width: 120px;">
                        <label class="form-label">Jumlah</label>
                        <input type="number" id="jumlah" name="jumlah" class="form-control" min="1"
                               value="<?= SecurityHelper::e((string)($edit['jumlah'] ?? 1)) ?>" required>
                    </div>
                </div>

                <div class="filter-row mb-3">
                    <div class="form-group flex-1">
                        <label class="form-label">Tanggal Sewa</label>
                        <input type="date" id="tanggal_sewa" name="tanggal_sewa" class="form-control"
                               value="<?= SecurityHelper::e($edit['tanggal_sewa'] ?? '') ?>" required>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Tanggal Kembali</label>
                        <input type="date" id="tanggal_kembali" name="tanggal_kembali" class="form-control"
                               value="<?= SecurityHelper::e($edit['tanggal_kembali'] ?? '') ?>" required>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Subtotal Terhitung</label>
                        <input type="text" id="subtotal" class="form-control" placeholder="Rp 0" readonly>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Status Pesanan</label>
                        <select name="status" class="form-control">
                            <?php foreach (['menunggu','diproses','disetujui','selesai','dibatalkan'] as $s): ?>
                                <option value="<?= $s ?>" <?= ($edit['status'] ?? '') === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="btn btn-primary">
                        <?= $edit ? 'Simpan Pembaruan' : 'Simpan Transaksi' ?>
                    </button>
                    <button type="button" id="btnResetTransaksi" class="btn btn-outline">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL DATA TRANSAKSI -->
        <div class="checkout-card">
            <h2 class="checkout-card-title">
                <span>📑</span> Daftar Semua Transaksi
            </h2>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Pelanggan</th>
                            <th>Peralatan</th>
                            <th>Gambar</th>
                            <th>Harga/Hari</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($data as $row):
                            $st = strtolower($row['status']);
                            $bClass = 'badge-warning';
                            if ($st === 'selesai' || $st === 'disetujui') $bClass = 'badge-success';
                            if ($st === 'dibatalkan') $bClass = 'badge-danger';
                            
                            $gambar = $row['gambar']
                                ? app_path("uploads/peralatan/" . SecurityHelper::e($row['gambar']))
                                : app_path("assets/img/no-image.png");
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td><strong><?= SecurityHelper::e($row['nama']) ?></strong></td>
                            <td><?= SecurityHelper::e($row['nama_peralatan']) ?></td>
                            <td class="text-center">
                                <img src="<?= $gambar ?>" alt="Item" class="img-product-table">
                            </td>
                            <td><?= SecurityHelper::rupiah($row['harga_sewa']) ?></td>
                            <td class="text-bold"><?= (int)$row['jumlah'] ?></td>
                            <td class="text-bold text-primary"><?= SecurityHelper::rupiah($row['subtotal']) ?></td>
                            <td>
                                <span class="badge <?= $bClass ?>"><?= strtoupper(SecurityHelper::e($row['status'])) ?></span>
                            </td>
                            <td class="cell-nowrap">
                                <a href="detail.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-outline btn-sm">Detail</a>
                                <a href="?edit=<?= urlencode($row['id_transaksi']) ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?hapus=<?= urlencode($row['id_transaksi']) ?>&csrf=<?= urlencode(SecurityHelper::csrfToken()) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-confirm="Hapus data transaksi ini secara permanen?">Hapus</a>
                            </td>
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
