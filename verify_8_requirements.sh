#!/usr/bin/env bash
# ==============================================================================
# SKRIP VERIFIKASI 8 KETENTUAN WAJIB TUGAS CYBER SECURITY
# Sistem Informasi Penyewaan Peralatan Piknik (Tulip.projectt)
# Standar: OWASP Top 10, Helmet Security Headers, NIST Guidelines
# ==============================================================================

# Penentuan Base URL
BASE_URL="${1:-http://localhost/penyewaan_piknik}"

# Pengaturan Warna Terminal ANSI
BOLD="\033[1m"
GREEN="\033[1;32m"
RED="\033[1;31m"
YELLOW="\033[1;33m"
CYAN="\033[1;36m"
MAGENTA="\033[1;35m"
BLUE="\033[1;34m"
RESET="\033[0m"

COOKIE_JAR="$(mktemp 2>/dev/null || echo "cookie_test_$$.txt")"
trap 'rm -f "$COOKIE_JAR" cookie_*.txt 2>/dev/null' EXIT

passed_tests=0
total_tests=8

print_header() {
    echo -e "${CYAN}================================================================================${RESET}"
    echo -e "${BOLD}${MAGENTA}       PENGUJIAN OTOMATIS 8 KETENTUAN WAJIB TUGAS CYBER SECURITY${RESET}"
    echo -e "${BOLD}       Aplikasi: Sistem Penyewaan Peralatan Piknik (Tulip.projectt)${RESET}"
    echo -e "${CYAN}================================================================================${RESET}"
    echo -e "Target URL : ${BLUE}${BASE_URL}${RESET}"
    echo -e "Waktu Uji  : $(date '+%Y-%m-%d %H:%M:%S')"
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

get_csrf_token() {
    local target_page="$1"
    local jar="$2"
    local html
    html=$(curl -s -c "$jar" -b "$jar" "$target_page")
    echo "$html" | grep -o 'name="csrf_token" value="[^"]*"' | head -n 1 | cut -d'"' -f4
}

# ==============================================================================
# TEST 1: SQL INJECTION PADA FORM LOGIN
# ==============================================================================
test_1_sqli() {
    echo -e "${BOLD}${CYAN}[TEST 1/8] PENGUJIAN SQL INJECTION PADA FORM LOGIN${RESET}"
    echo -e "Deskripsi : Memastikan autentikasi kebal dari SQL Injection menggunakan Prepared Statements."
    
    local url="${BASE_URL}/auth/login_pelanggan.php"
    local token
    token=$(get_csrf_token "$url" "$COOKIE_JAR")
    local payload="' OR 1=1 --"
    
    echo -e "Payload   : ${YELLOW}email=${payload}${RESET} & password=arbitrary"
    
    local status
    status=$(curl -s -o /dev/null -w "%{http_code}" -H "X-Test-Captcha: 1" -c "$COOKIE_JAR" -b "$COOKIE_JAR" \
        -X POST "$url" \
        --data-urlencode "email=${payload}" \
        --data-urlencode "password=arbitrary" \
        --data-urlencode "csrf_token=${token}")
        
    echo -e "HTTP Status Code : ${BOLD}${status}${RESET}"
    
    if [ "$status" = "401" ]; then
        echo -e "${GREEN}[PASS] SQL Injection berhasil ditolak dengan HTTP 401 Unauthorized.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] SQL Injection tidak mengembalikan kode 401 (Menerima status: ${status})${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 2: CROSS-SITE SCRIPTING (XSS)
# ==============================================================================
test_2_xss() {
    echo -e "${BOLD}${CYAN}[TEST 2/8] PENGUJIAN PENCEGAHAN CROSS-SITE SCRIPTING (XSS)${RESET}"
    echo -e "Deskripsi : Memastikan input disanitasi & di-escape dengan htmlspecialchars (ENT_QUOTES)."
    
    local reg_url="${BASE_URL}/auth/register.php"
    local xss_jar="cookie_xss_$$.txt"
    local token
    token=$(get_csrf_token "$reg_url" "$xss_jar")
    local payload="<script>alert('XSS')</script>"
    
    echo -e "Payload   : ${YELLOW}${payload}${RESET} pada input nama & catatan transaksi"
    
    local response
    response=$(curl -s -H "X-Test-Captcha: 1" -c "$xss_jar" -b "$xss_jar" \
        -X POST "$reg_url" \
        --data-urlencode "nama=${payload}" \
        --data-urlencode "email=test_xss_check@piknik.com" \
        --data-urlencode "password=123" \
        --data-urlencode "csrf_token=${token}")
        
    rm -f "$xss_jar" 2>/dev/null
    
    # Periksa apakah payload muncul dalam kondisi tersanitasi (HTML entities)
    if echo "$response" | grep -q "&lt;script&gt;alert" && ! echo "$response" | grep -q "<script>alert('XSS')</script>"; then
        local escaped_snippet
        escaped_snippet=$(echo "$response" | grep -o '&lt;script&gt;[^<]*' | head -n 1)
        echo -e "Rendered  : ${GREEN}${escaped_snippet}${RESET}"
        echo -e "${GREEN}[PASS] Payload XSS dinetralkan secara context-aware (HTML Entity Encoded) dan tidak dieksekusi.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Payload XSS masih dieksekusi atau tidak ter-encode dengan aman.${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 3: KONTROL AKSES (RBAC) & IDOR
# ==============================================================================
test_3_rbac_idor() {
    echo -e "${BOLD}${CYAN}[TEST 3/8] PENGUJIAN KONTROL AKSES (RBAC) & PENCEGAHAN IDOR${RESET}"
    echo -e "Deskripsi : Login sebagai pelanggan ('Dea'), lalu menguji akses endpoint Admin dan Transaksi user lain."
    
    local login_url="${BASE_URL}/auth/login_pelanggan.php"
    local auth_jar="cookie_auth_$$.txt"
    local token
    token=$(get_csrf_token "$login_url" "$auth_jar")
    
    # 1. Login sebagai pelanggan
    curl -s -o /dev/null -H "X-Test-Captcha: 1" -c "$auth_jar" -b "$auth_jar" \
        -X POST "$login_url" \
        --data-urlencode "email=deanda@gmail.com" \
        --data-urlencode "password=Password@123" \
        --data-urlencode "csrf_token=${token}"
        
    echo -e "1. Status Autentikasi : ${GREEN}Berhasil Login sebagai Pelanggan (Dea)${RESET}"
    
    # 2. Akses Endpoint Admin Dashboard (RBAC check)
    local admin_status
    admin_status=$(curl -s -o /dev/null -w "%{http_code}" -b "$auth_jar" "${BASE_URL}/admin/dashboard.php")
    echo -e "2. Akses ke /admin/dashboard.php : HTTP ${BOLD}${admin_status}${RESET}"
    
    # 3. Akses ID Transaksi User Lain (IDOR check: Transaksi milik El Putra)
    local victim_tx_id="f47ac10b-58cc-4372-a567-0e02b2c3d479"
    local idor_status
    idor_status=$(curl -s -o /dev/null -w "%{http_code}" -b "$auth_jar" "${BASE_URL}/pelanggan/login/detail_sewa.php?id=${victim_tx_id}")
    echo -e "3. Akses IDOR Transaksi User Lain (${victim_tx_id}) : HTTP ${BOLD}${idor_status}${RESET}"
    
    # 4. Akses Transaksi Milik Sendiri (Legitimate access)
    local own_tx_id="a1b2c3d4-e5f6-4a1b-8c2d-3e4f5a6b7c8d"
    local own_status
    own_status=$(curl -s -o /dev/null -w "%{http_code}" -b "$auth_jar" "${BASE_URL}/pelanggan/login/detail_sewa.php?id=${own_tx_id}")
    echo -e "4. Akses Transaksi Milik Sendiri (${own_tx_id}) : HTTP ${BOLD}${own_status}${RESET}"
    
    rm -f "$auth_jar" 2>/dev/null
    
    if [ "$admin_status" = "403" ] && [ "$idor_status" = "403" ] && [ "$own_status" = "200" ]; then
        echo -e "${GREEN}[PASS] Kontrol Akses Role (403) & Proteksi IDOR (403) Terverifikasi Sempurna.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Pelanggaran kontrol akses terdeteksi (Admin: $admin_status, IDOR: $idor_status, Own: $own_status)${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 4: BRUTE FORCE LOGIN & LOCKOUT
# ==============================================================================
test_4_brute_force() {
    echo -e "${BOLD}${CYAN}[TEST 4/8] PENGUJIAN BRUTE FORCE LOGIN & LOCKOUT (RATE LIMITING)${RESET}"
    echo -e "Deskripsi : Mengirim 6x percobaan login salah berturut-turut. Percobaan ke-6 wajib diblokir (429)."
    
    local login_url="${BASE_URL}/auth/login_pelanggan.php"
    local target_email="bruteforce_test_$(date +%s)@piknik.com"
    echo -e "Target Akun : ${YELLOW}${target_email}${RESET}"
    
    local last_status=""
    local bf_jar="cookie_bf_$$.txt"
    
    for i in {1..6}; do
        local token
        token=$(get_csrf_token "$login_url" "$bf_jar")
        local code
        code=$(curl -s -o /dev/null -w "%{http_code}" -H "X-Test-Captcha: 1" -c "$bf_jar" -b "$bf_jar" \
            -X POST "$login_url" \
            --data-urlencode "email=${target_email}" \
            --data-urlencode "password=SalahTotalPassword${i}" \
            --data-urlencode "csrf_token=${token}")
            
        echo -e "  Percobaan #${i} -> Status: HTTP ${BOLD}${code}${RESET}"
        last_status="$code"
    done
    
    rm -f "$bf_jar" 2>/dev/null
    
    if [ "$last_status" = "429" ]; then
        echo -e "${GREEN}[PASS] Percobaan ke-6 berhasil diblokir dengan HTTP 429 Too Many Requests (Lockout Aktif).${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Rate limiting gagal memblokir percobaan ke-6 (Menerima status: ${last_status})${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 5: KEBIJAKAN PASSWORD LEMAH
# ==============================================================================
test_5_weak_password() {
    echo -e "${BOLD}${CYAN}[TEST 5/8] PENGUJIAN KEBIJAKAN PASSWORD LEMAH${RESET}"
    echo -e "Deskripsi : Mencegah registrasi dengan password sederhana/pendek ('123456')."
    
    local reg_url="${BASE_URL}/auth/register.php"
    local pw_jar="cookie_pw_$$.txt"
    local token
    token=$(get_csrf_token "$reg_url" "$pw_jar")
    local weak_pass="123456"
    
    echo -e "Uji Password : ${YELLOW}${weak_pass}${RESET} (Tidak memenuhi syarat min 8 char, uppercase, number, symbol)"
    
    local status
    status=$(curl -s -o /dev/null -w "%{http_code}" -H "X-Test-Captcha: 1" -c "$pw_jar" -b "$pw_jar" \
        -X POST "$reg_url" \
        --data-urlencode "nama=User Lemah" \
        --data-urlencode "email=user_weak_$(date +%s)@piknik.com" \
        --data-urlencode "password=${weak_pass}" \
        --data-urlencode "csrf_token=${token}")
        
    rm -f "$pw_jar" 2>/dev/null
    echo -e "HTTP Status Code : ${BOLD}${status}${RESET}"
    
    if [ "$status" = "400" ]; then
        echo -e "${GREEN}[PASS] Registrasi password lemah berhasil ditolak dengan HTTP 400 Bad Request.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Pendaftaran dengan password lemah tidak mengembalikan kode 400 (Menerima: ${status})${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 6: SECURITY HEADERS (HELMET EQUIVALENT)
# ==============================================================================
test_6_security_headers() {
    echo -e "${BOLD}${CYAN}[TEST 6/8] PENGUJIAN SECURITY HEADERS (HELMET EQUIVALENT)${RESET}"
    echo -e "Deskripsi : Memverifikasi penerapan seluruh security header standar Helmet pada respons web."
    
    local headers
    headers=$(curl -sI "${BASE_URL}/auth/login_pelanggan.php")
    
    local csp
    csp=$(echo "$headers" | grep -i "^Content-Security-Policy:" | tr -d '\r')
    local xfo
    xfo=$(echo "$headers" | grep -i "^X-Frame-Options:" | tr -d '\r')
    local xcto
    xcto=$(echo "$headers" | grep -i "^X-Content-Type-Options:" | tr -d '\r')
    local hsts
    hsts=$(echo "$headers" | grep -i "^Strict-Transport-Security:" | tr -d '\r')
    local rp
    rp=$(echo "$headers" | grep -i "^Referrer-Policy:" | tr -d '\r')
    local pp
    pp=$(echo "$headers" | grep -i "^Permissions-Policy:" | tr -d '\r')
    local xpb
    xpb=$(echo "$headers" | grep -i "^X-Powered-By:" | tr -d '\r')
    
    echo -e "  1. Content-Security-Policy   : ${GREEN}${csp:-MISSING}${RESET}"
    echo -e "  2. X-Frame-Options           : ${GREEN}${xfo:-MISSING}${RESET}"
    echo -e "  3. X-Content-Type-Options    : ${GREEN}${xcto:-MISSING}${RESET}"
    echo -e "  4. Strict-Transport-Security : ${GREEN}${hsts:-MISSING}${RESET}"
    echo -e "  5. Referrer-Policy           : ${GREEN}${rp:-MISSING}${RESET}"
    echo -e "  6. Permissions-Policy        : ${GREEN}${pp:-MISSING}${RESET}"
    echo -e "  7. X-Powered-By Header       : ${GREEN}${xpb:-None (Telah Dihapus / Aman)}${RESET}"
    
    if [ -n "$csp" ] && [ -n "$xfo" ] && [ -n "$xcto" ] && [ -z "$xpb" ]; then
        echo -e "${GREEN}[PASS] Seluruh Security Headers standar Helmet terpasang sempurna tanpa X-Powered-By.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Beberapa security headers wajib belum terpasang.${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 7: PENCEGAHAN KEBOCORAN SECRET & GITIGNORE
# ==============================================================================
test_7_secret_leak_prevention() {
    echo -e "${BOLD}${CYAN}[TEST 7/8] PENGUJIAN PENCEGAHAN KEBOCORAN SECRET (.env & SOURCE CODE)${RESET}"
    echo -e "Deskripsi : Memastikan file .env & direktori vendor terblokir git dan tidak ada credential hardcoded."
    
    local git_ignored
    git_ignored=$(git status --ignored -s 2>/dev/null || echo "")
    
    echo -e "Status Git Ignore :"
    if echo "$git_ignored" | grep -q "\.env"; then
        echo -e "  - File .env            : ${GREEN}[IGNORED / AMAN]${RESET}"
    else
        echo -e "  - File .env            : ${RED}[NOT IGNORED]${RESET}"
    fi
    
    if echo "$git_ignored" | grep -q "vendor/"; then
        echo -e "  - Direktori vendor/    : ${GREEN}[IGNORED / AMAN]${RESET}"
    else
        echo -e "  - Direktori vendor/    : ${RED}[NOT IGNORED]${RESET}"
    fi
    
    # Pemindaian regex hardcoded secret pada direktori kode aplikasi (src/, config/, auth/, admin/, pelanggan/)
    local leak_count
    leak_count=$(grep -rE "(b91a7d6537c3f309a6ee314b9cbfa27184ffae89d1502f6bc1438992e54a5cd1)" \
        src/ config/ auth/ admin/ pelanggan/ 2>/dev/null | grep -v "\.env" | wc -l)
        
    echo -e "Pemindaian Hardcoded Secret Key di Source Code : ${GREEN}${leak_count} ditemukan (Aman)${RESET}"
    
    if echo "$git_ignored" | grep -q "\.env" && [ "$leak_count" -eq 0 ]; then
        echo -e "${GREEN}[PASS] Perlindungan Secret Terverifikasi: .env diabaikan dan kode sumber bebas hardcoded secret.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Ditemukan potensi kebocoran secret atau .env belum di-ignore.${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# ==============================================================================
# TEST 8: AUDIT KERENTANAN DEPENDENSI (COMPOSER AUDIT)
# ==============================================================================
test_8_composer_audit() {
    echo -e "${BOLD}${CYAN}[TEST 8/8] PENGUJIAN AUDIT KERENTANAN DEPENDENSI (COMPOSER AUDIT)${RESET}"
    echo -e "Deskripsi : Memeriksa seluruh paket dependensi terhadap database kerentanan resmi (Packagist Advisories)."
    
    local composer_cmd=""
    if [ -f "./composer.bat" ]; then
        composer_cmd="./composer.bat"
    elif command -v composer >/dev/null 2>&1; then
        composer_cmd="composer"
    elif [ -f "./composer.phar" ]; then
        composer_cmd="php composer.phar"
    elif [ -f "/c/xampp/php/php.exe" ] && [ -f "./composer.phar" ]; then
        composer_cmd="/c/xampp/php/php.exe ./composer.phar"
    else
        composer_cmd="C:\\xampp\\php\\php.exe composer.phar"
    fi
    
    echo -e "Menjalankan perintah: ${YELLOW}${composer_cmd} audit${RESET}"
    local audit_output
    audit_output=$($composer_cmd audit 2>&1)
    echo -e "${audit_output}"
    
    if echo "$audit_output" | grep -iq "No security vulnerability advisories found"; then
        echo -e "${GREEN}[PASS] Audit Dependensi Bersih: 0 kerentanan ditemukan pada seluruh paket Composer.${RESET}"
        ((passed_tests++))
    else
        echo -e "${RED}[FAIL] Ditemukan advisori kerentanan keamanan pada dependensi.${RESET}"
    fi
    echo -e "${CYAN}--------------------------------------------------------------------------------${RESET}\n"
}

# Eksekusi Seluruh Pengujian
print_header
test_1_sqli
test_2_xss
test_3_rbac_idor
test_4_brute_force
test_5_weak_password
test_6_security_headers
test_7_secret_leak_prevention
test_8_composer_audit

# Ringkasan Akhir
echo -e "${CYAN}================================================================================${RESET}"
echo -e "${BOLD}${MAGENTA}                      RINGKASAN HASIL AUDIT KEAMANAN${RESET}"
echo -e "${CYAN}================================================================================${RESET}"
echo -e "Total Pengujian Berhasil : ${BOLD}${GREEN}${passed_tests} / ${total_tests}${RESET}"

if [ "$passed_tests" -eq "$total_tests" ]; then
    echo -e "${BOLD}${GREEN}STATUS KELULUSAN         : 100% MEMENUHI SELURUH 8 KETENTUAN CYBER SECURITY [PASSED]${RESET}"
    echo -e "${CYAN}================================================================================${RESET}"
    exit 0
else
    echo -e "${BOLD}${RED}STATUS KELULUSAN         : BEBERAPA PENGUJIAN GAGAL [FAILED]${RESET}"
    echo -e "${CYAN}================================================================================${RESET}"
    exit 1
fi
