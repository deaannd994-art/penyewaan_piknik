<?php
require_once __DIR__ . '/../config/database.php';
require_auth(['admin'], app_path('auth/login_admin.php'));

// Metrics Ringkas Security Audit
$stmtTotal = $pdo->query("SELECT COUNT(*) AS total FROM audit_logs");
$totalLogs = (int)($stmtTotal->fetch()['total'] ?? 0);

$stmtBlocked = $pdo->query("
    SELECT COUNT(*) AS total FROM audit_logs 
    WHERE status = 'failed' 
       OR action LIKE '%BLOCKED%' 
       OR action LIKE '%ATTEMPT%' 
       OR action LIKE '%SQLI%' 
       OR action LIKE '%IDOR%'
");
$totalBlocked = (int)($stmtBlocked->fetch()['total'] ?? 0);

$stmtBf = $pdo->query("
    SELECT COUNT(*) AS total FROM audit_logs 
    WHERE action LIKE '%RATE_LIMIT%' OR action LIKE '%BRUTE_FORCE%'
");
$totalBf = (int)($stmtBf->fetch()['total'] ?? 0);

$stmtInjection = $pdo->query("
    SELECT COUNT(*) AS total FROM audit_logs 
    WHERE action LIKE '%SQLI%' OR action LIKE '%IDOR%' OR details LIKE '%OR 1=1%'
");
$totalInjection = (int)($stmtInjection->fetch()['total'] ?? 0);

// Query Data Logs Terbaru
$stmtLogs = $pdo->query("
    SELECT 
        a.id_log,
        a.id_user,
        a.action,
        a.target_resource,
        a.status,
        a.ip_address,
        a.user_agent,
        a.details,
        a.created_at,
        u.nama AS nama_user,
        u.email AS email_user
    FROM audit_logs a
    LEFT JOIN users u ON a.id_user = u.id_user
    ORDER BY a.created_at DESC
    LIMIT 200
");
$logs = $stmtLogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Security Audit Logs Monitor - Tulip.projectt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= app_path('public/assets/css/style.css') ?>">
</head>
<body>

<!-- HEADER -->
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
        <a href="dashboard.php" class="sidebar-nav-item">
            <span>🏠</span> Dashboard
        </a>
        <a href="peralatan/index.php" class="sidebar-nav-item">
            <span>🧺</span> Peralatan
        </a>
        <a href="transaksi/index.php" class="sidebar-nav-item">
            <span>💳</span> Transaksi
        </a>
        <a href="users/index.php" class="sidebar-nav-item">
            <span>👤</span> Data Users
        </a>
        <div class="sidebar-title">Keamanan & Audit</div>
        <a href="audit_logs.php" class="sidebar-nav-item active">
            <span>🛡️</span> Security Audit Logs
        </a>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <div class="page-header">
            <h1 class="page-title">🛡️ Security Audit Logs & Threat Monitor</h1>
            <p class="page-subtitle">Pemantauan real-time aktivitas sistem, mitigasi OWASP Top 10, dan pencegahan serangan cyber.</p>
        </div>

        <!-- 4-Card Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon info">📑</div>
                <div class="stat-card-label">Total Log Aktivitas</div>
                <div class="stat-card-value"><?= number_format($totalLogs) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon danger">⛔</div>
                <div class="stat-card-label">Total Serangan Diblokir</div>
                <div class="stat-card-value"><?= number_format($totalBlocked) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon warning">⚡</div>
                <div class="stat-card-label">Mitigasi Brute-Force</div>
                <div class="stat-card-value"><?= number_format($totalBf) ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon danger">🛑</div>
                <div class="stat-card-label">Percobaan SQLi & IDOR</div>
                <div class="stat-card-value"><?= number_format($totalInjection) ?></div>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="catalog-filter-card">
            <div class="filter-row">
                <div class="search-input-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="auditSearchInput" placeholder="Cari IP Address, Aksi, Endpoint, atau Rincian Serangan...">
                </div>

                <select id="auditStatusFilter" class="filter-select">
                    <option value="ALL">Semua Status (ALL)</option>
                    <option value="BLOCKED">🛑 Serangan Diblokir (BLOCKED / THREATS)</option>
                    <option value="FAILURE">⚠️ Gagal (FAILURE)</option>
                    <option value="SUCCESS">✅ Berhasil (SUCCESS)</option>
                </select>
            </div>
        </div>

        <!-- TABLE LOGS -->
        <div class="table-responsive">
            <table class="table-modern" id="auditLogTable">
                <thead>
                    <tr>
                        <th>Waktu (WIB)</th>
                        <th>Aktor / Pengguna</th>
                        <th>Aksi Sistem</th>
                        <th>Target Resource</th>
                        <th>IP Address</th>
                        <th>Status</th>
                        <th>Rincian Keamanan (Payload / Details)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <div class="empty-state-icon">📋</div>
                                <div class="empty-state-title">Belum Ada Catatan Audit</div>
                                <p class="empty-state-desc">Seluruh aktivitas autentikasi dan request sensitif akan tercatat otomatis di sini.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $actionUpper = strtoupper($log['action']);
                            $isAttack = false;
                            $attackBadge = '';

                            if (str_contains($actionUpper, 'SQLI') || (isset($log['details']) && (str_contains($log['details'], 'OR 1=1') || str_contains($log['details'], "'")))) {
                                $isAttack = true;
                                $attackBadge = '<span class="badge badge-sqli">SQLi Attempt</span>';
                            } elseif (str_contains($actionUpper, 'IDOR')) {
                                $isAttack = true;
                                $attackBadge = '<span class="badge badge-idor">IDOR Attempt</span>';
                            } elseif (str_contains($actionUpper, 'RATE_LIMIT') || str_contains($actionUpper, 'BRUTE_FORCE')) {
                                $isAttack = true;
                                $attackBadge = '<span class="badge badge-bruteforce">Brute-Force</span>';
                            } elseif (str_contains($actionUpper, 'UNAUTHORIZED')) {
                                $isAttack = true;
                                $attackBadge = '<span class="badge badge-unauthorized">Unauthorized Access</span>';
                            } elseif (str_contains($actionUpper, 'CAPTCHA')) {
                                $isAttack = true;
                                $attackBadge = '<span class="badge badge-danger">Bot / Invalid CAPTCHA</span>';
                            }
                        ?>
                        <tr data-status="<?= SecurityHelper::e($log['status']) ?>"
                            data-action="<?= SecurityHelper::e($log['action']) ?>"
                            data-blocked="<?= $isAttack || $log['status'] === 'failed' ? 'true' : 'false' ?>">
                            <td class="cell-nowrap">
                                <?= SecurityHelper::e(date('d M Y, H:i:s', strtotime($log['created_at']))) ?>
                            </td>
                            <td>
                                <?php if ($log['id_user']): ?>
                                    <strong><?= SecurityHelper::e($log['nama_user'] ?? 'User') ?></strong>
                                    <div class="cell-user-sub"><?= SecurityHelper::e($log['email_user'] ?? '') ?></div>
                                <?php else: ?>
                                    <span class="badge badge-neutral">Anonim / Publik</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?= SecurityHelper::e($log['action']) ?></code>
                                <?php if ($attackBadge): ?>
                                    <div class="badge-stack"><?= $attackBadge ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="cell-code"><?= SecurityHelper::e($log['target_resource']) ?></code>
                            </td>
                            <td>
                                <span class="badge badge-neutral"><?= SecurityHelper::e($log['ip_address'] ?? '127.0.0.1') ?></span>
                            </td>
                            <td>
                                <?php if ($log['status'] === 'success'): ?>
                                    <span class="badge badge-success">SUCCESS</span>
                                <?php else: ?>
                                    <span class="badge badge-danger badge-blocked">BLOCKED</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-details">
                                <?= SecurityHelper::e($log['details'] ?: '-') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- FOOTER -->
<footer class="site-footer">
    © <?= date('Y') ?> Tulip.projectt | Sistem Informasi Penyewaan Peralatan Piknik
</footer>

<script src="<?= app_path('public/assets/js/app.js') ?>"></script>
</body>
</html>
