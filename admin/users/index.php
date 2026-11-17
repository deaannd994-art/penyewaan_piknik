<?php
require_once __DIR__ . '/../../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

$error = '';

/* ========================================================
   SIMPAN / UPDATE USER
======================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['simpan']) || isset($_POST['update']))) {
    SecurityHelper::requireCsrf();

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
    $id = $_GET['hapus'];
    $csrf = $_GET['csrf'] ?? '';

    if (SecurityHelper::verifyCsrf($csrf) && is_valid_uuid($id)) {
        $stmtCekRole = $pdo->prepare("SELECT role FROM users WHERE id_user = :id LIMIT 1");
        $stmtCekRole->execute(['id' => $id]);
        $row = $stmtCekRole->fetch();

        if ($row && $row['role'] !== 'admin' && $row['role'] !== 'owner') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id_user = :id");
            $stmt->execute(['id' => $id]);

            log_audit($pdo, $_SESSION['id_user'], 'DELETE_USER', 'users', 'success', "ID: {$id}");
        }
    }
    header("Location: index.php");
    exit;
}

/* ========================================================
   EDIT USER
======================================================== */
$edit = null;
if (isset($_GET['edit']) && is_valid_uuid($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = :id LIMIT 1");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit = $stmt->fetch();
}

$stmtData = $pdo->query("SELECT * FROM users WHERE role = 'pelanggan' ORDER BY created_at DESC");
$data = $stmtData->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Users - Admin Tulip.projectt</title>
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
        <a href="../transaksi/index.php" class="sidebar-nav-item">
            <span>💳</span> Transaksi
        </a>
        <a href="index.php" class="sidebar-nav-item active">
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
            <h1 class="page-title">👤 Manajemen Pengguna Pelanggan</h1>
            <p class="page-subtitle">Kelola akun pelanggan, perbarui data kontak, atau daftarkan akun baru secara manual.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3">
                <span>⚠️</span>
                <div><?= SecurityHelper::e($error) ?></div>
            </div>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT USER -->
        <div class="checkout-card mb-4">
            <h2 class="checkout-card-title">
                <span>📝</span> <?= $edit ? 'Edit Data Pengguna' : 'Tambah Akun Pelanggan Baru' ?>
            </h2>

            <form method="POST" id="formUser">
                <?= SecurityHelper::csrfField() ?>

                <?php if ($edit): ?>
                    <input type="hidden" name="id_user" value="<?= SecurityHelper::e($edit['id_user']) ?>">
                <?php endif; ?>

                <div class="filter-row mb-3">
                    <div class="form-group flex-1">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control"
                               value="<?= SecurityHelper::e($edit['nama'] ?? '') ?>" required>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Alamat Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= SecurityHelper::e($edit['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Nomor HP / WhatsApp</label>
                        <input type="tel" name="no_hp" class="form-control"
                               value="<?= SecurityHelper::e($edit['no_hp'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="filter-row mb-3">
                    <div class="form-group flex-1">
                        <label class="form-label">Alamat Lengkap</label>
                        <input type="text" name="alamat" class="form-control"
                               value="<?= SecurityHelper::e($edit['alamat'] ?? '') ?>" required>
                    </div>

                    <div class="form-group flex-1">
                        <label class="form-label">Password <?= $edit ? '(Kosongkan jika tidak diubah)' : '(Min. 8 Karakter Kuat)' ?></label>
                        <input type="password" name="password" class="form-control"
                               <?= $edit ? '' : 'required' ?>>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" name="<?= $edit ? 'update' : 'simpan' ?>" class="btn btn-primary">
                        <?= $edit ? 'Simpan Pembaruan' : 'Daftarkan User' ?>
                    </button>
                    <button type="button" id="btnResetUser" class="btn btn-outline">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL LIST USERS -->
        <div class="checkout-card">
            <h2 class="checkout-card-title">
                <span>👥</span> Daftar Akun Pelanggan Terdaftar
            </h2>

            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>No HP</th>
                            <th>Alamat</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($data as $row): 
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td><strong><?= SecurityHelper::e($row['nama']) ?></strong></td>
                            <td><?= SecurityHelper::e($row['email']) ?></td>
                            <td><?= SecurityHelper::e($row['no_hp'] ?: '-') ?></td>
                            <td class="cell-details"><?= SecurityHelper::e($row['alamat'] ?: '-') ?></td>
                            <td class="cell-nowrap"><?= SecurityHelper::e(date('d M Y', strtotime($row['created_at']))) ?></td>
                            <td class="cell-nowrap">
                                <a href="detail.php?id=<?= urlencode($row['id_user']) ?>" class="btn btn-sm btn-outline">Detail</a>
                                <a href="?edit=<?= urlencode($row['id_user']) ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="?hapus=<?= urlencode($row['id_user']) ?>&csrf=<?= urlencode(SecurityHelper::csrfToken()) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-confirm="Hapus pengguna pelanggan ini?">Hapus</a>
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
