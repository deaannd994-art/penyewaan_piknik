<?php
require_once __DIR__ . '/../config/database.php';
init_secure_session('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityHelper::requireCsrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha  = trim($_POST['captcha_code'] ?? '');

    if (empty($email) || empty($password)) {
        http_response_code(400);
        $error = "Email dan password wajib diisi!";
    } elseif (!validate_captcha($captcha, $pdo, 'auth/login_admin.php')) {
        http_response_code(400);
        $error = "Kode verifikasi CAPTCHA salah atau telah kedaluwarsa. Silakan coba lagi.";
    } elseif (is_login_rate_limited($pdo, $email)) {
        http_response_code(429);
        $error = "Terlalu banyak percobaan login gagal. Akun/IP dibatasi sementara selama 15 menit.";
        log_audit($pdo, null, 'LOGIN_BLOCKED_RATE_LIMIT', 'auth/login_admin.php', 'failed', "Email: {$email}");
    } else {
        $stmt = $pdo->prepare("SELECT id_user, nama, email, password, role FROM users WHERE email = :email AND role IN ('admin', 'owner') LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && verify_password_peppered($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['login']   = true;
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];

            clear_failed_logins($pdo, $email);
            log_audit($pdo, $user['id_user'], 'LOGIN_SUCCESS', 'auth/login_admin.php', 'success', "Role: {$user['role']}");

            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
                exit;
            } elseif ($user['role'] === 'owner') {
                header("Location: ../owner/dashboard.php");
                exit;
            }
        } else {
            record_failed_login($pdo, $email);
            log_audit($pdo, null, 'LOGIN_FAILED', 'auth/login_admin.php', 'failed', "Email: {$email}");
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
    <title>Login Staf & Owner - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-brand">
                <span>🧺</span> Tulip.projectt
                <span class="brand-badge">Internal</span>
            </div>
            <h1 class="auth-title">Portal Admin & Owner</h1>
            <p class="auth-subtitle">Akses manajemen operasional, laporan keuangan, dan security audit.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <span>⚠️</span>
                <div><?= SecurityHelper::e($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?= SecurityHelper::csrfField() ?>

            <div class="form-group">
                <label class="form-label" for="adminEmail">Email Staf</label>
                <input type="email" id="adminEmail" name="email" class="form-control" 
                       value="<?= SecurityHelper::e($_POST['email'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="adminPassword">Password</label>
                <div class="password-field-wrapper">
                    <input type="password" id="adminPassword" name="password" class="form-control" required>
                    <button type="button" class="btn-toggle-password" data-target="adminPassword" aria-label="Lihat Password">
                        👁️‍🗨️
                    </button>
                </div>
            </div>

            <!-- KOTAK VERIFIKASI IMAGE CAPTCHA -->
            <div class="form-group">
                <label class="form-label" for="captchaCode">Verifikasi Keamanan (Bukan Robot)</label>
                <div class="captcha-box">
                    <img src="<?= app_path('public/captcha.php') ?>" alt="Security CAPTCHA" id="captchaAdminImg" class="captcha-img">
                    <button type="button" class="btn btn-outline btn-sm btn-refresh-captcha" data-target="captchaAdminImg" title="Perbarui Kode">
                        🔄 Refresh
                    </button>
                </div>
                <input type="text" id="captchaCode" name="captcha_code" class="form-control mt-2" 
                       placeholder="Ketik 6 karakter di atas..." maxlength="6" autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg mt-3">
                Masuk ke Portal
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= app_path('pelanggan/dashboard.php') ?>" class="text-sm text-muted">← Kembali ke Beranda</a>
        </div>
    </div>
</div>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
