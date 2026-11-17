<?php
/**
 * Security Headers Module (OWASP & Helmet Standard)
 * Sistem Penyewaan Peralatan Piknik (Tulip.projectt)
 * 
 * Mengimplementasikan perlindungan HTTP Header setara helmet.js:
 * 1. Content-Security-Policy (CSP)
 * 2. Strict-Transport-Security (HSTS)
 * 3. X-Content-Type-Options (nosniff)
 * 4. X-Frame-Options (DENY / Anti-Clickjacking)
 * 5. Referrer-Policy
 * 6. Permissions-Policy
 * 7. Cross-Origin-Opener-Policy (COOP)
 * 8. Cross-Origin-Resource-Policy (CORP)
 * 9. X-Permitted-Cross-Domain-Policies
 * 10. Penghapusan X-Powered-By (Information Exposure Prevention)
 */

class SecurityHeaders {
    public static function apply(): void {
        if (headers_sent()) {
            return;
        }

        // Hapus header expose teknologi (OWASP A05: Security Misconfiguration)
        header_remove('X-Powered-By');
        @ini_set('expose_php', 'off');

        // 1. Anti-Clickjacking
        header("X-Frame-Options: DENY");

        // 2. MIME Sniffing Prevention
        header("X-Content-Type-Options: nosniff");

        // 3. Strict-Transport-Security (HSTS: 180 days default Helmet standard)
        header("Strict-Transport-Security: max-age=15552000; includeSubDomains; preload");

        // 4. Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // 5. Permissions Policy (Restriksi sensor, kamera, mikrofon)
        header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");

        // 6. Cross-Origin Policies (COOP & CORP)
        header("Cross-Origin-Opener-Policy: same-origin");
        header("Cross-Origin-Resource-Policy: same-origin");

        // 7. Adobe Cross Domain Policies
        header("X-Permitted-Cross-Domain-Policies: none");

        // 8. DNS Prefetch Control
        header("X-DNS-Prefetch-Control: off");

        // 9. Download Options (IE8+)
        header("X-Download-Options: noopen");

        // 10. Content-Security-Policy (CSP)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                 || (isset($_SERVER['HTTP_HOST']) && str_contains(strtolower($_SERVER['HTTP_HOST']), 'vercel.app'))
                 || (!empty($_SERVER['VERCEL']) || !empty($_ENV['VERCEL']) || getenv('VERCEL') !== false);

        $csp = "default-src 'self'; "
             . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
             . "font-src 'self' https://fonts.gstatic.com data:; "
             . "img-src 'self' data: https: blob:; "
             . "frame-src 'self' https://www.google.com https://maps.google.com; "
             . "connect-src 'self'; "
             . "base-uri 'self'; "
             . "form-action 'self';"
             . ($isHttps ? " upgrade-insecure-requests;" : "");
        header("Content-Security-Policy: " . $csp);
    }

    public static function applyJson(): void {
        self::apply();
        header('Content-Type: application/json; charset=utf-8');
    }
}

// Auto-apply saat file ini dimuat
SecurityHeaders::apply();
