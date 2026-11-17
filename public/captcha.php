<?php
/**
 * Self-Hosted Image CAPTCHA Generator
 * Sistem Penyewaan Peralatan Piknik (Tulip.projectt)
 * Strict CSP Compliant (default-src 'self') & Offline-Ready
 */

if (file_exists(__DIR__ . '/../config/security.php')) {
    require_once __DIR__ . '/../config/security.php';
} elseif (file_exists(__DIR__ . '/config/security.php')) {
    require_once __DIR__ . '/config/security.php';
} else {
    require_once __DIR__ . '/../../config/security.php';
}

init_secure_session();

// 1. Generate 6 Karakter Alfanumerik Acak (Tanpa karakter ambigu 0, O, 1, I, l)
$charset = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$length  = 6;
$code    = '';
$maxIdx  = strlen($charset) - 1;

for ($i = 0; $i < $length; $i++) {
    $code .= $charset[random_int(0, $maxIdx)];
}

// 2. Simpan Jawaban Huruf Kapital dan Waktu Kedaluwarsa (3 Menit) ke Session
$_SESSION['captcha_code']    = strtoupper($code);
$_SESSION['captcha_expires'] = time() + 180; // 3 menit

// Mode API pengujian khusus lingkungan lokal jika diminta oleh skrip audit
if (isset($_GET['raw']) && (env('APP_ENV') === 'local' || env('APP_DEBUG') === 'true')) {
    header('Content-Type: text/plain; charset=utf-8');
    echo $code;
    exit;
}

// 3. Konfigurasi HTTP Header Anti-Cache
header("Content-Type: image/png");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// 4. Gambar CAPTCHA dengan PHP GD
$width  = 160;
$height = 46;

$image = imagecreatetruecolor($width, $height);

// Warna Tema Piknik Estetik (Cream, Forest Green, Terracotta)
$bgColor    = imagecolorallocate($image, 250, 248, 245); // #FAF8F5 (Cream)
$textColor  = imagecolorallocate($image, 45, 90, 39);    // #2D5A27 (Forest Green)
$textColor2 = imagecolorallocate($image, 31, 41, 55);    // #1F2937 (Dark Charcoal)
$lineColor1 = imagecolorallocate($image, 217, 119, 6);   // #D97706 (Terracotta)
$lineColor2 = imagecolorallocate($image, 163, 190, 160); // Soft green line
$dotColor   = imagecolorallocate($image, 180, 180, 180); // Gray noise dots

// Isi Latar Belakang
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// Bingkai Halus
$borderColor = imagecolorallocate($image, 229, 231, 235); // #E5E7EB
imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

// Noise: Titik-titik Acak
for ($i = 0; $i < 120; $i++) {
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
}

// Noise: Garis-garis Distorsi Halus
for ($i = 0; $i < 5; $i++) {
    $lineColor = ($i % 2 === 0) ? $lineColor1 : $lineColor2;
    imageline(
        $image,
        random_int(0, 30),
        random_int(0, $height),
        random_int($width - 30, $width),
        random_int(0, $height),
        $lineColor
    );
}

// Gambar Karakter Teks
$startX = 18;
$font   = 5; // Built-in GD font ukuran besar

for ($i = 0; $i < $length; $i++) {
    $charColor = ($i % 2 === 0) ? $textColor : $textColor2;
    $charY = random_int(12, 16);
    imagechar($image, $font, $startX + ($i * 22), $charY, $code[$i], $charColor);
}

// Output Gambar PNG
imagepng($image);
imagedestroy($image);
