<?php
/**
 * Front Controller & Router for Serverless Environments (Vercel)
 * Sistem Informasi Penyewaan Peralatan Piknik (Tulip.projectt)
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rawurldecode($uri);

// Strip project base directory prefix if accessed with subfolder
$uri = preg_replace('#^/penyewaan_piknik#i', '', $uri);

$rootDir = dirname(__DIR__);

// Route root path ('/' or empty) to root index.php
if ($uri === '' || $uri === '/' || $uri === '/index.php') {
    require $rootDir . '/index.php';
    exit;
}

// Target file path relative to root directory
$targetFile = $rootDir . '/' . ltrim($uri, '/');

// 1. Direct PHP file execution
if (is_file($targetFile) && pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
    require $targetFile;
    exit;
}

// 2. Directory with index.php
if (is_dir($targetFile) && is_file($targetFile . '/index.php')) {
    require $targetFile . '/index.php';
    exit;
}

// 3. Fallback for static assets if routed to PHP
if (is_file($targetFile)) {
    $mime = mime_content_type($targetFile) ?: 'application/octet-stream';
    header("Content-Type: {$mime}");
    readfile($targetFile);
    exit;
}

// 404 Not Found
http_response_code(404);
echo "404 Not Found: Halaman tidak ditemukan.";
exit;
