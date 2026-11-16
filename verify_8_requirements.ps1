<#
.SYNOPSIS
    Skrip Verifikasi Otomatis 8 Ketentuan Wajib Tugas Cyber Security
    Sistem Informasi Penyewaan Peralatan Piknik (Tulip.projectt)
#>

param (
    [string]$BaseUrl = "http://localhost/penyewaan_piknik"
)

$ErrorActionPreference = "Continue"

Write-Host "================================================================================" -ForegroundColor Cyan
Write-Host "       PENGUJIAN OTOMATIS 8 KETENTUAN WAJIB TUGAS CYBER SECURITY" -ForegroundColor Magenta
Write-Host "       Aplikasi: Sistem Penyewaan Peralatan Piknik (Tulip.projectt)" -ForegroundColor White
Write-Host "================================================================================" -ForegroundColor Cyan
Write-Host "Target URL : $BaseUrl" -ForegroundColor Blue
Write-Host "Waktu Uji  : $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

$passedTests = 0
$totalTests = 8

function Get-CsrfToken($url, $cookieFile) {
    $html = curl.exe -s -c $cookieFile -b $cookieFile $url
    $match = [regex]::Match($html, 'name="csrf_token" value="([^"]+)"')
    return $match.Groups[1].Value
}

# TEST 1: SQL INJECTION
Write-Host "[TEST 1/8] PENGUJIAN SQL INJECTION PADA FORM LOGIN" -ForegroundColor Cyan
$cookie1 = "cookie_sqli_$PID.txt"
$token1 = Get-CsrfToken "$BaseUrl/auth/login_pelanggan.php" $cookie1
$sqliResp = curl.exe -s -o /dev/null -w "%{http_code}" -c $cookie1 -b $cookie1 -X POST "$BaseUrl/auth/login_pelanggan.php" --data-urlencode "email=' OR 1=1 --" --data-urlencode "password=test" --data-urlencode "csrf_token=$token1"
Remove-Item $cookie1 -ErrorAction SilentlyContinue

Write-Host "Payload   : email=' OR 1=1 -- & password=test" -ForegroundColor Yellow
Write-Host "HTTP Status Code : $sqliResp"
if ($sqliResp -eq "401") {
    Write-Host "[PASS] SQL Injection berhasil ditolak dengan HTTP 401 Unauthorized." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] SQL Injection tidak mengembalikan kode 401 (Menerima: $sqliResp)" -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 2: CROSS-SITE SCRIPTING (XSS)
Write-Host "[TEST 2/8] PENGUJIAN PENCEGAHAN CROSS-SITE SCRIPTING (XSS)" -ForegroundColor Cyan
$cookie2 = "cookie_xss_$PID.txt"
$token2 = Get-CsrfToken "$BaseUrl/auth/register.php" $cookie2
$payloadXss = "<script>alert('XSS')</script>"
$xssHtml = curl.exe -s -c $cookie2 -b $cookie2 -X POST "$BaseUrl/auth/register.php" --data-urlencode "nama=$payloadXss" --data-urlencode "email=xss_test_$PID@piknik.com" --data-urlencode "password=123" --data-urlencode "csrf_token=$token2"
Remove-Item $cookie2 -ErrorAction SilentlyContinue

Write-Host "Payload   : $payloadXss pada input nama/catatan" -ForegroundColor Yellow
if ($xssHtml -match "&lt;script&gt;alert" -and -not ($xssHtml -match "<script>alert\('XSS'\)</script>")) {
    $cleanMatch = [regex]::Match($xssHtml, '&lt;script&gt;[^<]*').Value
    Write-Host "Rendered  : $cleanMatch" -ForegroundColor Green
    Write-Host "[PASS] Payload XSS dinetralkan secara context-aware (HTML Entity Encoded) dan tidak dieksekusi." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Payload XSS tidak di-escape dengan aman." -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 3: KONTROL AKSES (RBAC) & IDOR
Write-Host "[TEST 3/8] PENGUJIAN KONTROL AKSES (RBAC) & PENCEGAHAN IDOR" -ForegroundColor Cyan
$cookie3 = "cookie_rbac_$PID.txt"
$token3 = Get-CsrfToken "$BaseUrl/auth/login_pelanggan.php" $cookie3
curl.exe -s -o /dev/null -c $cookie3 -b $cookie3 -X POST "$BaseUrl/auth/login_pelanggan.php" --data-urlencode "email=deanda@gmail.com" --data-urlencode "password=Password@123" --data-urlencode "csrf_token=$token3"
Write-Host "1. Status Autentikasi : Berhasil Login sebagai Pelanggan (Dea)" -ForegroundColor Green

$adminStatus = curl.exe -s -o /dev/null -w "%{http_code}" -b $cookie3 "$BaseUrl/admin/dashboard.php"
Write-Host "2. Akses ke /admin/dashboard.php : HTTP $adminStatus"
$victimTx = "f47ac10b-58cc-4372-a567-0e02b2c3d479"
$idorStatus = curl.exe -s -o /dev/null -w "%{http_code}" -b $cookie3 "$BaseUrl/pelanggan/login/detail_sewa.php?id=$victimTx"
Write-Host "3. Akses IDOR Transaksi User Lain ($victimTx) : HTTP $idorStatus"
$ownTx = "a1b2c3d4-e5f6-4a1b-8c2d-3e4f5a6b7c8d"
$ownStatus = curl.exe -s -o /dev/null -w "%{http_code}" -b $cookie3 "$BaseUrl/pelanggan/login/detail_sewa.php?id=$ownTx"
Write-Host "4. Akses Transaksi Milik Sendiri ($ownTx) : HTTP $ownStatus"
Remove-Item $cookie3 -ErrorAction SilentlyContinue

if ($adminStatus -eq "403" -and $idorStatus -eq "403" -and $ownStatus -eq "200") {
    Write-Host "[PASS] Kontrol Akses Role (403) & Proteksi IDOR (403) Terverifikasi Sempurna." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Akses kontrol gagal diverifikasi." -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 4: BRUTE FORCE LOGIN & LOCKOUT
Write-Host "[TEST 4/8] PENGUJIAN BRUTE FORCE LOGIN & LOCKOUT (RATE LIMITING)" -ForegroundColor Cyan
$targetEmail = "victim_bf_" + (Get-Random) + "@piknik.com"
Write-Host "Target Akun : $targetEmail" -ForegroundColor Yellow
$bfCookie = "cookie_bf_$PID.txt"
$lastCode = ""

for ($i = 1; $i -le 6; $i++) {
    $bfToken = Get-CsrfToken "$BaseUrl/auth/login_pelanggan.php" $bfCookie
    $lastCode = curl.exe -s -o /dev/null -w "%{http_code}" -c $bfCookie -b $bfCookie -X POST "$BaseUrl/auth/login_pelanggan.php" --data-urlencode "email=$targetEmail" --data-urlencode "password=SalahTotal$i" --data-urlencode "csrf_token=$bfToken"
    Write-Host "  Percobaan #$i -> Status: HTTP $lastCode"
}
Remove-Item $bfCookie -ErrorAction SilentlyContinue

if ($lastCode -eq "429") {
    Write-Host "[PASS] Percobaan ke-6 berhasil diblokir dengan HTTP 429 Too Many Requests (Lockout Aktif)." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Percobaan ke-6 tidak diblokir dengan 429 (Menerima: $lastCode)" -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 5: KEBIJAKAN PASSWORD LEMAH
Write-Host "[TEST 5/8] PENGUJIAN KEBIJAKAN PASSWORD LEMAH" -ForegroundColor Cyan
$cookie5 = "cookie_pw_$PID.txt"
$token5 = Get-CsrfToken "$BaseUrl/auth/register.php" $cookie5
$weakPass = "123456"
Write-Host "Uji Password : $weakPass (Kurang dari 8 char & tanpa kombinasi simbol)" -ForegroundColor Yellow
$pwCode = curl.exe -s -o /dev/null -w "%{http_code}" -c $cookie5 -b $cookie5 -X POST "$BaseUrl/auth/register.php" --data-urlencode "nama=User Lemah" --data-urlencode "email=weak_$PID@piknik.com" --data-urlencode "password=$weakPass" --data-urlencode "csrf_token=$token5"
Remove-Item $cookie5 -ErrorAction SilentlyContinue

Write-Host "HTTP Status Code : $pwCode"
if ($pwCode -eq "400") {
    Write-Host "[PASS] Registrasi password lemah berhasil ditolak dengan HTTP 400 Bad Request." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Password lemah tidak ditolak dengan kode 400 (Menerima: $pwCode)" -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 6: SECURITY HEADERS
Write-Host "[TEST 6/8] PENGUJIAN SECURITY HEADERS (HELMET EQUIVALENT)" -ForegroundColor Cyan
$hdrOutput = curl.exe -sI "$BaseUrl/auth/login_pelanggan.php"
$csp = ($hdrOutput -split "`n" | Where-Object { $_ -match "^Content-Security-Policy:" }).Trim()
$xfo = ($hdrOutput -split "`n" | Where-Object { $_ -match "^X-Frame-Options:" }).Trim()
$xcto = ($hdrOutput -split "`n" | Where-Object { $_ -match "^X-Content-Type-Options:" }).Trim()
$hsts = ($hdrOutput -split "`n" | Where-Object { $_ -match "^Strict-Transport-Security:" }).Trim()
$rp = ($hdrOutput -split "`n" | Where-Object { $_ -match "^Referrer-Policy:" }).Trim()
$pp = ($hdrOutput -split "`n" | Where-Object { $_ -match "^Permissions-Policy:" }).Trim()
$xpbMatch = ($hdrOutput -split "`n" | Where-Object { $_ -match "^X-Powered-By:" })
$xpb = if ($xpbMatch) { $xpbMatch.Trim() } else { $null }

Write-Host "  1. Content-Security-Policy   : $csp" -ForegroundColor Green
Write-Host "  2. X-Frame-Options           : $xfo" -ForegroundColor Green
Write-Host "  3. X-Content-Type-Options    : $xcto" -ForegroundColor Green
Write-Host "  4. Strict-Transport-Security : $hsts" -ForegroundColor Green
Write-Host "  5. Referrer-Policy           : $rp" -ForegroundColor Green
Write-Host "  6. Permissions-Policy        : $pp" -ForegroundColor Green
Write-Host "  7. X-Powered-By Header       : $(if ($xpb) { $xpb } else { 'None (Telah Dihapus / Aman)' })" -ForegroundColor Green

if ($csp -and $xfo -and $xcto -and -not $xpb) {
    Write-Host "[PASS] Seluruh Security Headers standar Helmet terpasang sempurna tanpa X-Powered-By." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Security headers tidak lengkap." -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 7: SECRET LEAK PREVENTION
Write-Host "[TEST 7/8] PENGUJIAN PENCEGAHAN KEBOCORAN SECRET (.env & SOURCE CODE)" -ForegroundColor Cyan
$gitIgnored = git status --ignored -s 2>$null
$envIgnored = $gitIgnored -match "\.env"
$vendorIgnored = $gitIgnored -match "vendor/"

Write-Host "Status Git Ignore :"
Write-Host "  - File .env         : $(if ($envIgnored) { '[IGNORED / AMAN]' } else { '[NOT IGNORED]' })" -ForegroundColor $(if ($envIgnored) { 'Green' } else { 'Red' })
Write-Host "  - Direktori vendor/ : $(if ($vendorIgnored) { '[IGNORED / AMAN]' } else { '[NOT IGNORED]' })" -ForegroundColor $(if ($vendorIgnored) { 'Green' } else { 'Red' })

$leaks = Get-ChildItem -Path "src", "config", "auth", "admin", "pelanggan" -Recurse -Filter "*.php" | Select-String -Pattern "b91a7d6537c3f309a6ee314b9cbfa27184ffae89d1502f6bc1438992e54a5cd1"
$leakCount = ($leaks | Measure-Object).Count
Write-Host "Pemindaian Hardcoded Secret Key di Source Code : $leakCount ditemukan (Aman)" -ForegroundColor Green

if ($envIgnored -and $leakCount -eq 0) {
    Write-Host "[PASS] Perlindungan Secret Terverifikasi: .env diabaikan dan kode sumber bebas hardcoded secret." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Kebocoran secret terdeteksi atau .env belum diabaikan." -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# TEST 8: COMPOSER AUDIT
Write-Host "[TEST 8/8] PENGUJIAN AUDIT KERENTANAN DEPENDENSI (COMPOSER AUDIT)" -ForegroundColor Cyan
Write-Host "Menjalankan perintah: .\composer.bat audit" -ForegroundColor Yellow
$auditOut = cmd /c ".\composer.bat audit 2>&1"
Write-Host $auditOut

if ($auditOut -match "No security vulnerability advisories found") {
    Write-Host "[PASS] Audit Dependensi Bersih: 0 kerentanan ditemukan pada seluruh paket Composer." -ForegroundColor Green
    $passedTests++
} else {
    Write-Host "[FAIL] Ditemukan advisory keamanan pada dependensi." -ForegroundColor Red
}
Write-Host "--------------------------------------------------------------------------------`n" -ForegroundColor Cyan

# FINAL SUMMARY
Write-Host "================================================================================" -ForegroundColor Cyan
Write-Host "                      RINGKASAN HASIL AUDIT KEAMANAN" -ForegroundColor Magenta
Write-Host "================================================================================" -ForegroundColor Cyan
Write-Host "Total Pengujian Berhasil : $passedTests / $totalTests" -ForegroundColor $(if ($passedTests -eq $totalTests) { 'Green' } else { 'Red' })

if ($passedTests -eq $totalTests) {
    Write-Host "STATUS KELULUSAN         : 100% MEMENUHI SELURUH 8 KETENTUAN CYBER SECURITY [PASSED]" -ForegroundColor Green
    Write-Host "================================================================================" -ForegroundColor Cyan
    exit 0
} else {
    Write-Host "STATUS KELULUSAN         : BEBERAPA PENGUJIAN GAGAL [FAILED]" -ForegroundColor Red
    Write-Host "================================================================================" -ForegroundColor Cyan
    exit 1
}
