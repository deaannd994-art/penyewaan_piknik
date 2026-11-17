<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

$error = '';
$success = '';

/* ========================================================
   TOGGLE STATUS
======================================================== */
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $csrf = $_GET['csrf'] ?? '';

    if (SecurityHelper::verifyCsrf($csrf) && is_valid_uuid($id)) {
        $stmt = $pdo->prepare("SELECT status FROM peralatan WHERE id_peralatan = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            $statusBaru = ($row['status'] === 'tersedia') ? 'tidak tersedia' : 'tersedia';
            $updateStmt = $pdo->prepare("UPDATE peralatan SET status = :status WHERE id_peralatan = :id");
            $updateStmt->execute(['status' => $statusBaru, 'id' => $id]);

            log_audit($pdo, $_SESSION['id_user'], 'TOGGLE_STATUS_PERALATAN', 'peralatan', 'success', "ID: {$id} -> {$statusBaru}");
        }
    }
    header("Location: index.php");
    exit;
}

/* ========================================================
   TAMBAH & UPDATE
======================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    SecurityHelper::requireCsrf();

    $aksi      = $_POST['aksi'];
    $nama      = trim($_POST['nama_peralatan'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga     = filter_var($_POST['harga_sewa'] ?? 0, FILTER_VALIDATE_INT);
    $stok      = filter_var($_POST['stok'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($nama) || $harga === false || $stok === false || $harga < 0 || $stok < 0) {
        $error = "Data peralatan tidak valid!";
    } else {
        $gambarName = null;

        // Validasi Upload Gambar Aman (OWASP File Upload Hardening)
        if (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['gambar']['tmp_name'];
            $fileSize = $_FILES['gambar']['size'];

            if ($fileSize > 5 * 1024 * 1024) { // Max 5MB
                $error = "Ukuran gambar terlalu besar! Maksimal 5MB.";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($fileTmp);
                $allowedMimes = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedMimes[$mimeType])) {
                    $error = "Format file tidak didukung! Hanya JPG, PNG, dan WEBP yang diizinkan.";
                } else {
                    $ext = $allowedMimes[$mimeType];
                    $gambarName = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = __DIR__ . "/../../uploads/peralatan/" . $gambarName;

                    if (!move_uploaded_file($fileTmp, $targetPath)) {
                        $error = "Gagal mengunggah gambar.";
                        $gambarName = null;
                    }
                }
            }
        }

        if (empty($error)) {
            if ($aksi === 'tambah') {
                $newId = generate_uuid();
                $stmtIns = $pdo->prepare("
                    INSERT INTO peralatan (id_peralatan, nama_peralatan, deskripsi, harga_sewa, stok, status, gambar)
                    VALUES (:id, :nama, :deskripsi, :harga, :stok, 'tersedia', :gambar)
                ");
                $stmtIns->execute([
                    'id'        => $newId,
                    'nama'      => $nama,
                    'deskripsi' => $deskripsi,
                    'harga'     => $harga,
                    'stok'      => $stok,
                    'gambar'    => $gambarName ?? 'default.jpg'
                ]);

                log_audit($pdo, $_SESSION['id_user'], 'CREATE_PERALATAN', 'peralatan', 'success', "ID: {$newId}");
                header("Location: index.php");
                exit;
            } elseif ($aksi === 'update') {
                $id = $_POST['id_peralatan'] ?? '';
                if (!is_valid_uuid($id)) {
                    $error = "ID peralatan tidak valid!";
                } else {
                    if ($gambarName) {
                        $stmtUp = $pdo->prepare("
                            UPDATE peralatan 
                            SET nama_peralatan = :nama, deskripsi = :deskripsi, harga_sewa = :harga, stok = :stok, gambar = :gambar
                            WHERE id_peralatan = :id
                        ");
                        $stmtUp->execute([
                            'nama'      => $nama,
                            'deskripsi' => $deskripsi,
                            'harga'     => $harga,
                            'stok'      => $stok,
                            'gambar'    => $gambarName,
                            'id'        => $id
                        ]);
                    } else {
                        $stmtUp = $pdo->prepare("
                            UPDATE peralatan 
                            SET nama_peralatan = :nama, deskripsi = :deskripsi, harga_sewa = :harga, stok = :stok
                            WHERE id_peralatan = :id
                        ");
                        $stmtUp->execute([
                            'nama'      => $nama,
                            'deskripsi' => $deskripsi,
                            'harga'     => $harga,
                            'stok'      => $stok,
                            'id'        => $id
                        ]);
                    }

                    log_audit($pdo, $_SESSION['id_user'], 'UPDATE_PERALATAN', 'peralatan', 'success', "ID: {$id}");
                    header("Location: index.php");
                    exit;
                }
            }
        }
    }
}

/* ========================================================
   HAPUS PERALATAN
======================================================== */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $csrf = $_GET['csrf'] ?? '';

    if (SecurityHelper::verifyCsrf($csrf) && is_valid_uuid($id)) {
        $stmt = $pdo->prepare("DELETE FROM peralatan WHERE id_peralatan = :id");
        $stmt->execute(['id' => $id]);

        log_audit($pdo, $_SESSION['id_user'], 'DELETE_PERALATAN', 'peralatan', 'success', "ID: {$id}");
    }
    header("Location: index.php");
    exit;
}

/* ========================================================
   EDIT FORM DATA
======================================================== */
$edit = null;
if (isset($_GET['edit']) && is_valid_uuid($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM peralatan WHERE id_peralatan = :id LIMIT 1");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit = $stmt->fetch();
}

$stmtList = $pdo->query("SELECT * FROM peralatan ORDER BY nama_peralatan ASC");
$listPeralatan = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Peralatan - Admin Tulip.projectt</title>
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
        <a href="index.php" class="sidebar-nav-item active">
            <span>🧺</span> Peralatan
        </a>
        <a href="../transaksi/index.php" class="sidebar-nav-item">
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
            <h1 class="page-title">🧺 Manajemen Peralatan & Paket Piknik</h1>
            <p class="page-subtitle">Tambah peralatan baru, perbarui tarif sewa, stok, atau ubah status ketersediaan.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <span>⚠️</span>
                <div><?= SecurityHelper::e($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT -->
        <div class="checkout-card mb-4">
            <h2 class="checkout-card-title">
                <span>📝</span> <?= $edit ? 'Edit Paket Peralatan' : 'Tambah Paket Peralatan Baru' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" id="formPeralatan">
                <?= SecurityHelper::csrfField() ?>
                <input type="hidden" name="aksi" value="<?= $edit ? 'update' : 'tambah' ?>">

                <?php if ($edit): ?>
                    <input type="hidden" name="id_peralatan" value="<?= SecurityHelper::e($edit['id_peralatan']) ?>">
                <?php endif; ?>

                <div class="filter-row mb-3">
                    <div class="form-group flex-1">
                        <label class="form-label">Nama Peralatan / Paket</label>
                        <input type="text" name="nama_peralatan" class="form-control"
                               value="<?= SecurityHelper::e($edit['nama_peralatan'] ?? '') ?>" required>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Tarif Sewa / Hari (Rp)</label>
                        <input type="number" name="harga_sewa" class="form-control"
                               value="<?= SecurityHelper::e((string)($edit['harga_sewa'] ?? '')) ?>" min="0" required>
                    </div>

                    <div class="form-group" style="min-width: 140px;">
                        <label class="form-label">Jumlah Stok</label>
                        <input type="number" name="stok" class="form-control"
                               value="<?= SecurityHelper::e((string)($edit['stok'] ?? '')) ?>" min="0" required>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Deskripsi Lengkap & Komponen Set</label>
                    <textarea name="deskripsi" class="form-control" rows="3" required><?= SecurityHelper::e($edit['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Foto Produk (JPG/PNG/WEBP, Max 5MB)</label>
                    <input type="file" name="gambar" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($edit['gambar'])): ?>
                        <div class="d-flex align-center gap-2 mt-2">
                            <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($edit['gambar'])) ?>" 
                                 class="img-product-table" alt="Foto Saat Ini">
                            <span class="text-xs text-muted">Gambar saat ini: <?= SecurityHelper::e($edit['gambar']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?= $edit ? 'Simpan Perubahan' : 'Tambah Peralatan' ?>
                    </button>
                    <button type="button" id="btnResetPeralatan" class="btn btn-outline">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL LIST PERALATAN -->
        <div class="checkout-card">
            <h2 class="checkout-card-title">
                <span>🧺</span> Daftar Inventaris Peralatan
            </h2>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Gambar</th>
                            <th>Nama Peralatan</th>
                            <th>Deskripsi</th>
                            <th>Harga Sewa</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($listPeralatan as $row): 
                            $isAvail = ($row['status'] === 'tersedia');
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td>
                                <?php if ($row['gambar']): ?>
                                    <img src="<?= app_path('uploads/peralatan/' . SecurityHelper::e($row['gambar'])) ?>" 
                                         class="img-product-table" alt="<?= SecurityHelper::e($row['nama_peralatan']) ?>">
                                <?php endif; ?>
                            </td>
                            <td><strong><?= SecurityHelper::e($row['nama_peralatan']) ?></strong></td>
                            <td class="cell-details"><?= nl2br(SecurityHelper::e($row['deskripsi'])) ?></td>
                            <td class="text-bold text-primary"><?= SecurityHelper::rupiah($row['harga_sewa']) ?></td>
                            <td class="text-bold"><?= (int)$row['stok'] ?> unit</td>
                            <td>
                                <a href="?toggle_status=<?= urlencode($row['id_peralatan']) ?>&csrf=<?= urlencode(SecurityHelper::csrfToken()) ?>"
                                   data-confirm="Ubah status ketersediaan peralatan ini?">
                                    <?php if ($isAvail): ?>
                                        <span class="badge badge-success">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Tidak Tersedia</span>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="cell-nowrap">
                                <a href="?edit=<?= urlencode($row['id_peralatan']) ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?hapus=<?= urlencode($row['id_peralatan']) ?>&csrf=<?= urlencode(SecurityHelper::csrfToken()) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-confirm="Hapus peralatan ini dari database?">Hapus</a>
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
