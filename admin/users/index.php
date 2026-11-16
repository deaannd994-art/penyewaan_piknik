<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

$error = '';

/* ========================================================
   SIMPAN / UPDATE USER
======================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['simpan']) || isset($_POST['update']))) {
    require_csrf();

    $nama   = trim($_POST['nama'] ?? '');
    $email  = strtolower(trim($_POST['email'] ?? ''));
    $no_hp  = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if (empty($nama) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Nama dan format email wajib valid!";
    } else {
        // SIMPAN USER BARU
        if (isset($_POST['simpan'])) {
            $password = $_POST['password'] ?? '';
            if (!is_strong_password($password)) {
                $error = "Password minimal 8 karakter dan harus mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol!";
            } else {
                // Cek duplikasi email
                $stmtCek = $pdo->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
                $stmtCek->execute(['email' => $email]);
                if ($stmtCek->fetch()) {
                    $error = "Email sudah terdaftar!";
                } else {
                    $newId = generate_uuid();
                    $hash = hash_password_peppered($password);

                    $stmtIn = $pdo->prepare("
                        INSERT INTO users (id_user, nama, email, no_hp, alamat, password, role, created_at)
                        VALUES (:id, :nama, :email, :no_hp, :alamat, :pass, 'pelanggan', NOW())
                    ");
                    $stmtIn->execute([
                        'id'     => $newId,
                        'nama'   => $nama,
                        'email'  => $email,
                        'no_hp'  => $no_hp,
                        'alamat' => $alamat,
                        'pass'   => $hash
                    ]);

                    log_audit($pdo, $_SESSION['id_user'], 'CREATE_USER', 'users', 'success', "ID: {$newId}, Email: {$email}");
                    header("Location: index.php");
                    exit;
                }
            }
        }

        // UPDATE USER
        if (isset($_POST['update'])) {
            $id_user = $_POST['id_user'] ?? '';
            if (!is_valid_uuid($id_user)) {
                $error = "ID User tidak valid!";
            } else {
                // Cek apakah email digunakan user lain
                $stmtCek = $pdo->prepare("SELECT id_user FROM users WHERE email = :email AND id_user != :id LIMIT 1");
                $stmtCek->execute(['email' => $email, 'id' => $id_user]);
                if ($stmtCek->fetch()) {
                    $error = "Email sudah digunakan oleh user lain!";
                } else {
                    if (!empty($_POST['password'])) {
                        $password = $_POST['password'];
                        if (!is_strong_password($password)) {
                            $error = "Password baru minimal 8 karakter dan harus mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol!";
                        } else {
                            $hash = hash_password_peppered($password);
                            $stmtUp = $pdo->prepare("
                                UPDATE users SET
                                    nama     = :nama,
                                    email    = :email,
                                    no_hp    = :no_hp,
                                    alamat   = :alamat,
                                    password = :pass
                                WHERE id_user = :id
                            ");
                            $stmtUp->execute([
                                'nama'   => $nama,
                                'email'  => $email,
                                'no_hp'  => $no_hp,
                                'alamat' => $alamat,
                                'pass'   => $hash,
                                'id'     => $id_user
                            ]);
                        }
                    } else {
                        $stmtUp = $pdo->prepare("
                            UPDATE users SET
                                nama   = :nama,
                                email  = :email,
                                no_hp  = :no_hp,
                                alamat = :alamat
                            WHERE id_user = :id
                        ");
                        $stmtUp->execute([
                            'nama'   => $nama,
                            'email'  => $email,
                            'no_hp'  => $no_hp,
                            'alamat' => $alamat,
                            'id'     => $id_user
                        ]);
                    }

                    if (empty($error)) {
                        log_audit($pdo, $_SESSION['id_user'], 'UPDATE_USER', 'users', 'success', "ID: {$id_user}");
                        header("Location: index.php");
                        exit;
                    }
                }
            }
        }
    }
}

/* ========================================================
   HAPUS USER
======================================================== */
if (isset($_GET['hapus'])) {
    $id_user = $_GET['hapus'];
    $csrf = $_GET['csrf'] ?? '';

    if (verify_csrf_token($csrf) && is_valid_uuid($id_user)) {
        // Jangan izinkan admin menghapus akunnya sendiri
        if ($id_user !== $_SESSION['id_user']) {
            $stmtDel = $pdo->prepare("DELETE FROM users WHERE id_user = :id");
            $stmtDel->execute(['id' => $id_user]);

            log_audit($pdo, $_SESSION['id_user'], 'DELETE_USER', 'users', 'success', "ID: {$id_user}");
        }
    }
    header("Location: index.php");
    exit;
}

/* ========================================================
   EDIT DATA
======================================================== */
$edit = null;
if (isset($_GET['edit']) && is_valid_uuid($_GET['edit'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM users WHERE id_user = :id LIMIT 1");
    $stmtEdit->execute(['id' => $_GET['edit']]);
    $edit = $stmtEdit->fetch();
}

/* ========================================================
   DATA USERS PELANGGAN
======================================================== */
$stmtData = $pdo->query("
    SELECT id_user, nama, email, no_hp, alamat, created_at
    FROM users
    WHERE role = 'pelanggan'
    ORDER BY created_at DESC
");
$data = $stmtData->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Users</title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
        <a href="../transaksi/index.php">💳 Transaksi</a>
        <a href="index.php" class="active">👤 Users</a>
    </div>

    <div class="content">

        <?php if ($error): ?>
            <div style="background:#fee; color:#c00; padding:10px 15px; border-radius:6px; margin-bottom:15px;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><?= $edit ? 'Edit User' : 'Tambah User' ?></h3>

            <form method="post" class="form-grid" id="formUser">
                <?= csrf_field() ?>

                <?php if ($edit): ?>
                    <input type="hidden" name="id_user" value="<?= e($edit['id_user']) ?>">
                <?php endif; ?>

                <input type="text"
                       name="nama"
                       placeholder="Nama"
                       value="<?= e($edit['nama'] ?? '') ?>"
                       required>

                <input type="email"
                       name="email"
                       placeholder="Email"
                       value="<?= e($edit['email'] ?? '') ?>"
                       required>

                <input type="text"
                       name="no_hp"
                       placeholder="No HP"
                       value="<?= e($edit['no_hp'] ?? '') ?>"
                       required>

                <input type="text"
                       name="alamat"
                       placeholder="Alamat"
                       value="<?= e($edit['alamat'] ?? '') ?>"
                       required>

                <input type="password"
                       name="password"
                       placeholder="<?= $edit ? 'Kosongkan jika tidak diubah' : 'Password (Min 6 Karakter)' ?>"
                       <?= $edit ? '' : 'required' ?>>

                <button name="<?= $edit ? 'update' : 'simpan' ?>" class="btn">
                    <?= $edit ? 'Update' : 'Simpan' ?>
                </button>

                <button type="button" class="btn-sm" onclick="resetForm()">Batal</button>
            </form>

            <hr>

            <h3>Daftar Users Pelanggan</h3>

            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No HP</th>
                        <th>Alamat</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php $no = 1; foreach ($data as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['no_hp'] ?? '-') ?></td>
                        <td><?= e($row['alamat'] ?? '-') ?></td>
                        <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="detail.php?id=<?= urlencode($row['id_user']) ?>" class="btn-sm logout">Detail</a>
                            <a href="?edit=<?= urlencode($row['id_user']) ?>" class="btn-sm edit">Edit</a>
                            <a href="?hapus=<?= urlencode($row['id_user']) ?>&csrf=<?= urlencode(get_csrf_token()) ?>"
                               class="btn-sm delete"
                               onclick="return confirm('Yakin hapus user ini?')">
                                Hapus
                            </a>
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
function resetForm() {
    const form = document.getElementById('formUser');
    form.reset();
    if (window.location.search.includes('edit=')) {
        window.location.href = 'index.php';
    }
}
</script>

</body>
</html>
