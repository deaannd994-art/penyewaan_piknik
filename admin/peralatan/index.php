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

    if (verify_csrf_token($csrf) && is_valid_uuid($id)) {
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
    require_csrf();

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
            /* TAMBAH DATA (Generate UUID v4) */
            if ($aksi === 'tambah') {
                $newId = generate_uuid();
                $status = 'tersedia';

                $stmt = $pdo->prepare("
                    INSERT INTO peralatan (id_peralatan, nama_peralatan, deskripsi, gambar, harga_sewa, stok, status)
                    VALUES (:id, :nama, :deskripsi, :gambar, :harga, :stok, :status)
                ");
                $stmt->execute([
                    'id'        => $newId,
                    'nama'      => $nama,
                    'deskripsi' => $deskripsi,
                    'gambar'    => $gambarName,
                    'harga'     => $harga,
                    'stok'      => $stok,
                    'status'    => $status
                ]);

                log_audit($pdo, $_SESSION['id_user'], 'CREATE_PERALATAN', 'peralatan', 'success', "ID: {$newId}, Nama: {$nama}");
                header("Location: index.php");
                exit;
            }

            /* UPDATE DATA */
            if ($aksi === 'update') {
                $id = $_POST['id_peralatan'] ?? '';
                if (!is_valid_uuid($id)) {
                    $error = "ID Peralatan tidak valid!";
                } else {
                    if ($gambarName) {
                        $stmt = $pdo->prepare("
                            UPDATE peralatan SET
                                nama_peralatan = :nama,
                                deskripsi      = :deskripsi,
                                gambar         = :gambar,
                                harga_sewa     = :harga,
                                stok           = :stok
                            WHERE id_peralatan = :id
                        ");
                        $stmt->execute([
                            'nama'      => $nama,
                            'deskripsi' => $deskripsi,
                            'gambar'    => $gambarName,
                            'harga'     => $harga,
                            'stok'      => $stok,
                            'id'        => $id
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE peralatan SET
                                nama_peralatan = :nama,
                                deskripsi      = :deskripsi,
                                harga_sewa     = :harga,
                                stok           = :stok
                            WHERE id_peralatan = :id
                        ");
                        $stmt->execute([
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

    if (verify_csrf_token($csrf) && is_valid_uuid($id)) {
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

/* ========================================================
   LIST SEMUA PERALATAN
======================================================== */
$stmtList = $pdo->query("SELECT * FROM peralatan ORDER BY nama_peralatan ASC");
$listPeralatan = $stmtList->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - Peralatan</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
        <a href="index.php" class="active">🧺 Peralatan</a>
        <a href="../transaksi/index.php">💳 Transaksi</a>
        <a href="../users/index.php">👤 Users</a>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <?php if ($error): ?>
            <div style="background:#fee; color:#c00; padding:10px 15px; border-radius:6px; margin-bottom:15px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <div class="card">
            <h3><?= $edit ? 'Edit Peralatan' : 'Tambah Peralatan' ?></h3>

            <form method="POST" enctype="multipart/form-data" class="form-grid" id="formPeralatan">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="<?= $edit ? 'update' : 'tambah' ?>">

                <?php if ($edit): ?>
                    <input type="hidden" name="id_peralatan" value="<?= e($edit['id_peralatan']) ?>">
                <?php endif; ?>

                <input type="text"
                       name="nama_peralatan"
                       placeholder="Nama Peralatan"
                       value="<?= e($edit['nama_peralatan'] ?? '') ?>"
                       required>

                <textarea name="deskripsi"
                          placeholder="Deskripsi"
                          required><?= e($edit['deskripsi'] ?? '') ?></textarea>

                <div>
                    <label style="display:block; margin-bottom:4px; font-size:13px; color:#666;">Pilih Gambar (JPG/PNG/WEBP, Max 5MB):</label>
                    <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
                </div>

                <?php if (!empty($edit['gambar'])): ?>
                    <img src="../../uploads/peralatan/<?= e($edit['gambar']) ?>"
                         width="120"
                         style="margin-top:10px; border-radius:8px;">
                <?php endif; ?>

                <input type="number"
                       name="harga_sewa"
                       placeholder="Harga Sewa"
                       value="<?= e((string)($edit['harga_sewa'] ?? '')) ?>"
                       min="0"
                       required>

                <input type="number"
                       name="stok"
                       placeholder="Stok"
                       value="<?= e((string)($edit['stok'] ?? '')) ?>"
                       min="0"
                       required>

                <button type="submit" class="btn"><?= $edit ? 'Update' : 'Simpan' ?></button>
                <button type="button" class="btn-sm" onclick="resetForm()">Batal</button>
            </form>
        </div>

        <!-- TABEL -->
        <div class="card">
            <h3>Daftar Peralatan</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Gambar</th>
                        <th>Nama</th>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($listPeralatan as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <?php if ($row['gambar']): ?>
                                <img src="../../uploads/peralatan/<?= e($row['gambar']) ?>" width="60" style="border-radius:4px;">
                            <?php endif; ?>
                        </td>
                        <td><?= e($row['nama_peralatan']) ?></td>
                        <td><?= nl2br(e($row['deskripsi'])) ?></td>
                        <td>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                        <td><?= e((string)$row['stok']) ?></td>
                        <td>
                            <a href="?toggle_status=<?= urlencode($row['id_peralatan']) ?>&csrf=<?= urlencode(get_csrf_token()) ?>"
                               onclick="return confirm('Ubah status peralatan?')"
                               style="text-decoration:none">
                                <?php if ($row['status'] === 'tersedia'): ?>
                                    <span class="badge success">Tersedia</span>
                                <?php else: ?>
                                    <span class="badge danger">Tidak tersedia</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <a href="?edit=<?= urlencode($row['id_peralatan']) ?>" class="btn-sm edit">Edit</a>
                            <a href="?hapus=<?= urlencode($row['id_peralatan']) ?>&csrf=<?= urlencode(get_csrf_token()) ?>"
                               class="btn-sm delete"
                               onclick="return confirm('Hapus peralatan ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    © <?= date('Y') ?> Admin | Penyewaan Peralatan Piknik
</div>

<script>
function resetForm() {
    const form = document.getElementById('formPeralatan');
    form.reset();
    if (window.location.search.includes('edit=')) {
        window.location.href = 'index.php';
    }
}
</script>

</body>
</html>
