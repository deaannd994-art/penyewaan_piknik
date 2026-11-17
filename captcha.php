<?php
/**
 * Self-Hosted Image CAPTCHA Generator
 * Sistem Penyewaan Peralatan Piknik (Tulip.projectt)
 * Strict CSP Compliant (default-src 'self') & Offline-Ready
 */

// Bersihkan output buffer terdahulu agar tidak merusak binary/XML output
while (ob_get_level()) {
    ob_end_clean();
}

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
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$width  = 160;
$height = 46;

// 4. Render Menggunakan PHP GD (jika ekstensi tersedia)
$gdAvailable = extension_loaded('gd') && function_exists('imagecreatetruecolor') && function_exists('imagepng');

if ($gdAvailable) {
    try {
        $image = @imagecreatetruecolor($width, $height);
        if ($image !== false) {
            $bgColor    = imagecolorallocate($image, 250, 248, 245); // #FAF8F5 (Cream)
            $textColor  = imagecolorallocate($image, 45, 90, 39);    // #2D5A27 (Forest Green)
            $textColor2 = imagecolorallocate($image, 31, 41, 55);    // #1F2937 (Dark Charcoal)
            $lineColor1 = imagecolorallocate($image, 217, 119, 6);   // #D97706 (Terracotta)
            $lineColor2 = imagecolorallocate($image, 163, 190, 160); // Soft green line
            $dotColor   = imagecolorallocate($image, 180, 180, 180); // Gray noise dots
            $borderColor = imagecolorallocate($image, 229, 231, 235); // #E5E7EB

            imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
            imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

            // Noise: Titik-titik Acak
            for ($i = 0; $i < 100; $i++) {
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
            $font   = 5;
            for ($i = 0; $i < $length; $i++) {
                $charColor = ($i % 2 === 0) ? $textColor : $textColor2;
                $charY = random_int(12, 16);
                imagechar($image, $font, $startX + ($i * 22), $charY, $code[$i], $charColor);
            }

            header("Content-Type: image/png");
            imagepng($image);
            imagedestroy($image);
            exit;
        }
    } catch (\Throwable $t) {
        // Fallback otomatis ke SVG jika GD mengalami kegagalan
    }
}

// 5. Fallback Otomatis: SVG Vector CAPTCHA (Selalu didukung di Serverless Cloud/Vercel)
header("Content-Type: image/svg+xml; charset=utf-8");
$lineColors = ['#D97706', '#A3BEA0', '#CBD5E1', '#F59E0B', '#94A3B8'];
$charColors = ['#2D5A27', '#1F2937', '#15803D', '#374151', '#065F46'];

$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
$svg .= '<rect width="100%" height="100%" fill="#FAF8F5" stroke="#E5E7EB" stroke-width="1"/>';

// Noise Lines
for ($i = 0; $i < 6; $i++) {
    $x1 = random_int(0, 30);
    $y1 = random_int(0, $height);
    $x2 = random_int($width - 30, $width);
    $y2 = random_int(0, $height);
    $color = $lineColors[$i % count($lineColors)];
    $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="1.5" opacity="0.65"/>';
}

// Noise Dots
for ($i = 0; $i < 35; $i++) {
    $cx = random_int(5, $width - 5);
    $cy = random_int(5, $height - 5);
    $r  = random_int(1, 2);
    $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="#94A3B8" opacity="0.5"/>';
}

// Characters with slight distortion and rotation
$startX = 18;
for ($i = 0; $i < strlen($code); $i++) {
    $char = htmlspecialchars($code[$i], ENT_QUOTES, 'UTF-8');
    $color = $charColors[$i % count($charColors)];
    $x = $startX + ($i * 22);
    $y = random_int(28, 33);
    $rot = random_int(-15, 15);
    $svg .= '<text x="' . $x . '" y="' . $y . '" font-family="Arial, Helvetica, sans-serif" font-size="22" font-weight="bold" fill="' . $color . '" transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')">' . $char . '</text>';
}

$svg .= '</svg>';
echo $svg;
exit;
