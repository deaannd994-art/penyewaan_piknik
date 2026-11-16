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

    if (verify_csrf_token($csrf) && is_valid_uuid($id)) {
        // detail_transaksi memiliki ON DELETE CASCADE, namun kita eksekusi aman via PDO
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
    require_csrf();

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
        // Ambil harga sewa peralatan
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

            /* UPDATE */
            if (isset($_POST['update'])) {
                $id_transaksi = $_POST['id_transaksi'] ?? '';
                if (!is_valid_uuid($id_transaksi)) {
                    $error = "ID Transaksi tidak valid!";
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
    <title>Admin | Data Transaksi</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .img-thumb {
            width:60px;
            height:60px;
            object-fit:cover;
            border-radius:6px;
            border:1px solid #ddd;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="logo">🧺 Tulip.projectt</div>
    <div class="logo">Admin | Penyewaan Peralatan Piknik</div>
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

        <?php if ($error): ?>
            <div style="background:#fee; color:#c00; padding:10px 15px; border-radius:6px; margin-bottom:15px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><?= $edit ? 'Edit Transaksi' : 'Tambah Transaksi' ?></h2>

            <form method="post" class="form-grid" id="formTransaksi">
                <?= csrf_field() ?>

                <?php if ($edit): ?>
                    <input type="hidden" name="id_transaksi" value="<?= e($edit['id_transaksi']) ?>">
                <?php endif; ?>

                <select name="id_user" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= e($u['id_user']) ?>"
                            <?= ($edit['id_user'] ?? '') === $u['id_user'] ? 'selected' : '' ?>>
                            <?= e($u['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="id_peralatan" id="id_peralatan" required>
                    <option value="">-- Pilih Peralatan --</option>
                    <?php foreach ($peralatan as $p): ?>
                        <option value="<?= e($p['id_peralatan']) ?>"
                            data-harga="<?= (int)$p['harga_sewa'] ?>"
                            <?= ($edit['id_peralatan'] ?? '') === $p['id_peralatan'] ? 'selected' : '' ?>>
                            <?= e($p['nama_peralatan']) ?> (Rp <?= number_format($p['harga_sewa'], 0, ',', '.') ?>/hari)
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="number" id="jumlah" name="jumlah" placeholder="Jumlah" min="1"
                       value="<?= e((string)($edit['jumlah'] ?? 1)) ?>" required>

                <input type="date" id="tanggal_sewa" name="tanggal_sewa"
                       value="<?= e($edit['tanggal_sewa'] ?? '') ?>" required>

                <input type="date" id="tanggal_kembali" name="tanggal_kembali"
                       value="<?= e($edit['tanggal_kembali'] ?? '') ?>" required>

                <input type="text" id="subtotal" placeholder="Subtotal" readonly>

                <select name="status">
                    <?php foreach (['menunggu','diproses','disetujui','selesai','dibatalkan'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($edit['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button name="<?= $edit ? 'update' : 'simpan' ?>" class="btn">
                    <?= $edit ? 'Update' : 'Simpan' ?>
                </button>

                <button type="button" class="btn-sm" onclick="resetForm()">Batal</button>
            </form>
            <hr>

            <h2>Data Transaksi</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>User</th>
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
                <?php $no = 1; foreach ($data as $row):
                    $badge = match($row['status']) {
                        'menunggu'   => 'warning',
                        'diproses'   => 'secondary',
                        'disetujui'  => 'info',
                        'selesai'    => 'success',
                        'dibatalkan' => 'danger',
                        default      => 'secondary'
                    };
                    $gambar = $row['gambar']
                        ? "../../uploads/peralatan/" . $row['gambar']
                        : "../../assets/img/no-image.png";
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['nama_peralatan']) ?></td>
                        <td align="center">
                            <img src="<?= e($gambar) ?>" class="img-thumb">
                        </td>
                        <td>Rp <?= number_format($row['harga_sewa'], 0, ',', '.') ?></td>
                        <td><?= e((string)$row['jumlah']) ?></td>
                        <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                        <td><span class="badge <?= $badge ?>"><?= ucfirst(e($row['status'])) ?></span></td>
                        <td>
                            <a href="detail.php?id=<?= urlencode($row['id_transaksi']) ?>" class="btn-sm logout">Detail</a>
                            <a href="?edit=<?= urlencode($row['id_transaksi']) ?>" class="btn-sm edit">Edit</a>
                            <a href="?hapus=<?= urlencode($row['id_transaksi']) ?>&csrf=<?= urlencode(get_csrf_token()) ?>"
                               class="btn-sm delete"
                               onclick="return confirm('Hapus transaksi?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="footer">
    © <?= date('Y') ?> Admin | Penyewaan Peralatan Piknik
</div>

<script>
function hitungSubtotal() {
    const p = document.getElementById('id_peralatan');
    const j = document.getElementById('jumlah').value;
    const s = document.getElementById('tanggal_sewa').value;
    const k = document.getElementById('tanggal_kembali').value;

    if (!p.value || !s || !k) return;

    const harga = p.options[p.selectedIndex].dataset.harga || 0;
    const hari  = Math.max(1, Math.floor((new Date(k) - new Date(s)) / 86400000) + 1);
    document.getElementById('subtotal').value = (harga * j * hari).toLocaleString('id-ID');
}

['id_peralatan','jumlah','tanggal_sewa','tanggal_kembali'].forEach(id => {
    document.getElementById(id).addEventListener('change', hitungSubtotal);
});

hitungSubtotal();

function resetForm() {
    const form = document.getElementById('formTransaksi');
    form.reset();
    if (window.location.search.includes('edit=')) {
        window.location.href = 'index.php';
    }
}
</script>

</body>
</html>
