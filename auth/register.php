<?php
require_once __DIR__ . '/../config/database.php';
init_secure_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $nama     = trim($_POST['nama'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $no_hp    = trim($_POST['no_hp'] ?? '');
    $alamat   = trim($_POST['alamat'] ?? '');
    $role     = 'pelanggan';

    if (empty($nama) || empty($email) || empty($password)) {
        http_response_code(400);
        $error = "Nama, email, dan password wajib diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        $error = "Format email tidak valid!";
    } elseif (!is_strong_password($password)) {
        http_response_code(400);
        $error = "Password minimal 8 karakter dan wajib mengandung kombinasi huruf besar, huruf kecil, angka, dan simbol!";
    } else {
        // Cek email duplikat
        $cek = $pdo->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
        $cek->execute(['email' => $email]);
        if ($cek->fetch()) {
            http_response_code(409);
            $error = "Email sudah terdaftar!";
        } else {
            $id_user = generate_uuid();
            $hash = hash_password_peppered($password);

            $stmt = $pdo->prepare("
                INSERT INTO users (id_user, nama, email, password, role, no_hp, alamat, created_at)
                VALUES (:id_user, :nama, :email, :password, :role, :no_hp, :alamat, NOW())
            ");
            $success = $stmt->execute([
                'id_user'  => $id_user,
                'nama'     => $nama,
                'email'    => $email,
                'password' => $hash,
                'role'     => $role,
                'no_hp'    => $no_hp,
                'alamat'   => $alamat
            ]);

            if ($success) {
                session_regenerate_id(true);
                $_SESSION['login']   = true;
                $_SESSION['id_user'] = $id_user;
                $_SESSION['nama']    = $nama;
                $_SESSION['role']    = $role;

                log_audit($pdo, $id_user, 'USER_REGISTER', 'auth/register.php', 'success', "Email: {$email}");

                header("Location: ../pelanggan/login/dashboard.php");
                exit;
            } else {
                $error = "Registrasi gagal, silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Pelanggan</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <h2>Registrasi Pelanggan
        <br>🧺 Tulip.projectt
    </h2>

    <?php if ($error): ?>
        <p style="color:red; text-align:center;"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Password (Min. 6 Karakter)</label>
            <input type="password" name="password" minlength="6" required>
        </div>

        <div class="form-group">
            <label>No HP</label>
            <input type="text" name="no_hp" value="<?= e($_POST['no_hp'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Alamat</label>
            <input type="text" name="alamat" value="<?= e($_POST['alamat'] ?? '') ?>">
        </div>

        <button class="btn" type="submit">Daftar</button>
    </form>

    <div class="auth-footer">
        <a href="login_pelanggan.php">Sudah punya akun? Login</a>
    </div>
</div>

</body>
</html>
