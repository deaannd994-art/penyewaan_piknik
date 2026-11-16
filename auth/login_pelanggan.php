<?php
require_once __DIR__ . '/../config/database.php';
init_secure_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        $error = "Email dan password wajib diisi!";
    } elseif (is_login_rate_limited($pdo, $email)) {
        http_response_code(429);
        $error = "Terlalu banyak percobaan login gagal. Percobaan login dibatasi selama 15 menit.";
        log_audit($pdo, null, 'LOGIN_BLOCKED_RATE_LIMIT', 'auth/login_pelanggan.php', 'failed', "Email: {$email}");
    } else {
        $stmt = $pdo->prepare("SELECT id_user, nama, email, password, role FROM users WHERE email = :email AND role = 'pelanggan' LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && verify_password_peppered($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['login']   = true;
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = 'pelanggan';

            clear_failed_logins($pdo, $email);
            log_audit($pdo, $user['id_user'], 'LOGIN_SUCCESS', 'auth/login_pelanggan.php', 'success');

            // Mitigasi Open Redirect (OWASP A01)
            if (!empty($_SESSION['redirect'])) {
                $tujuan = $_SESSION['redirect'];
                unset($_SESSION['redirect']);
                // Hanya izinkan redirect internal relative path (OWASP A01: Anti Open-Redirect)
                if (str_starts_with($tujuan, '/') && !str_starts_with($tujuan, '//') && !str_starts_with($tujuan, '/\\') && !str_contains($tujuan, ':')) {
                    header("Location: " . $tujuan);
                    exit;
                }
            }
            header("Location: ../pelanggan/login/dashboard.php");
            exit;
        } else {
            record_failed_login($pdo, $email);
            log_audit($pdo, null, 'LOGIN_FAILED', 'auth/login_pelanggan.php', 'failed', "Email: {$email}");
            http_response_code(401);
            $error = "Email atau password salah!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Pelanggan</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <h2>Login Pelanggan
        <br>🧺 Tulip.projectt
    </h2>

    <?php if ($error): ?>
        <p style="color:red; text-align:center;"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" class="btn">Login</button>
    </form>

    <div class="auth-footer">
        Belum punya akun? <a href="register.php">Daftar sekarang</a>
    </div>
</div>

</body>
</html>
