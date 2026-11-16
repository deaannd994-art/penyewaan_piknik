<?php
/**
 * Environment Loader Module
 * Membaca file .env dan mengekspor ke getenv(), $_ENV, dan $_SERVER
 * Standard: 100% Native PHP tanpa dependensi pihak ketiga
 */

if (!function_exists('load_env')) {
    function load_env(string $path): void {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Hapus tanda kutip jika ada
                if (
                    (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))
                ) {
                    $val = substr($val, 1, -1);
                }

                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        if ($val === 'true' || $val === '(true)') {
            return true;
        }
        if ($val === 'false' || $val === '(false)') {
            return false;
        }
        if ($val === 'null' || $val === '(null)') {
            return null;
        }
        if ($val === 'empty' || $val === '(empty)') {
            return '';
        }

        return $val;
    }
}

// Inisialisasi pemuatan .env dari root proyek
$rootPath = dirname(__DIR__);
if (file_exists($rootPath . '/vendor/autoload.php')) {
    require_once $rootPath . '/vendor/autoload.php';
    if (class_exists(\Dotenv\Dotenv::class)) {
        try {
            $dotenv = \Dotenv\Dotenv::createImmutable($rootPath);
            $dotenv->safeLoad();
        } catch (\Throwable $th) {
            load_env($rootPath . '/.env');
        }
    } else {
        load_env($rootPath . '/.env');
    }
} else {
    load_env($rootPath . '/.env');
}
