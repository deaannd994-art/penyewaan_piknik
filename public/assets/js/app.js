/**
 * SISTEM PENYEWAAN PERALATAN PIKNIK (Tulip.projectt)
 * Master Client Interaction Script
 * 100% Strict CSP Compliant (No Inline Handlers / Zero Inline Code)
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initUserDropdown();
    initPasswordToggle();
    initPasswordStrength();
    initCatalogFilter();
    initCartCalculator();
    initCheckoutCalculator();
    initImagePreview();
    initDashboardCharts();
    initAuditLogFilter();
});

/* ==========================================================================
   1. NAVIGATION & HAMBURGER TOGGLE
   ========================================================================== */
function initNavigation() {
    const toggleBtn = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (toggleBtn && navMenu) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = navMenu.classList.toggle('is-open');
            toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!navMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
                navMenu.classList.remove('is-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
}

/* ==========================================================================
   2. USER DROPDOWN MENU
   ========================================================================== */
function initUserDropdown() {
    const dropdowns = document.querySelectorAll('.user-dropdown');

    dropdowns.forEach(dropdown => {
        const btn = dropdown.querySelector('.user-dropdown-btn');
        const menu = dropdown.querySelector('.user-dropdown-menu');

        if (btn && menu) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                // Close others
                document.querySelectorAll('.user-dropdown-menu.is-open').forEach(m => {
                    if (m !== menu) m.classList.remove('is-open');
                });
                menu.classList.toggle('is-open');
            });
        }
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.user-dropdown-menu.is-open').forEach(m => {
            m.classList.remove('is-open');
        });
    });
}

/* ==========================================================================
   3. PASSWORD SHOW / HIDE TOGGLE
   ========================================================================== */
function initPasswordToggle() {
    const toggleBtns = document.querySelectorAll('.btn-toggle-password');

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = targetId ? document.getElementById(targetId) : btn.parentElement.querySelector('input');

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '👁️';
                btn.setAttribute('aria-label', 'Sembunyikan Password');
                btn.title = 'Sembunyikan Password';
            } else {
                input.type = 'password';
                btn.innerHTML = '👁️‍🗨️';
                btn.setAttribute('aria-label', 'Tampilkan Password');
                btn.title = 'Tampilkan Password';
            }
        });
    });
}

/* ==========================================================================
   4. REAL-TIME PASSWORD STRENGTH INDICATOR (Register Page)
   ========================================================================== */
function initPasswordStrength() {
    const passwordInput = document.getElementById('regPassword');
    const meterBar = document.getElementById('passwordMeterBar');
    const meterText = document.getElementById('passwordMeterText');

    if (!passwordInput || !meterBar) return;

    const critLength = document.getElementById('crit-length');
    const critUpper = document.getElementById('crit-upper');
    const critLower = document.getElementById('crit-lower');
    const critNumber = document.getElementById('crit-number');
    const critSymbol = document.getElementById('crit-symbol');

    passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        let score = 0;

        // Criteria 1: Min 8 chars
        const hasLength = val.length >= 8;
        updateCriteriaStatus(critLength, hasLength);
        if (hasLength) score++;

        // Criteria 2: Uppercase
        const hasUpper = /[A-Z]/.test(val);
        updateCriteriaStatus(critUpper, hasUpper);
        if (hasUpper) score++;

        // Criteria 3: Lowercase
        const hasLower = /[a-z]/.test(val);
        updateCriteriaStatus(critLower, hasLower);
        if (hasLower) score++;

        // Criteria 4: Number
        const hasNumber = /[0-9]/.test(val);
        updateCriteriaStatus(critNumber, hasNumber);
        if (hasNumber) score++;

        // Criteria 5: Symbol / Special Char
        const hasSymbol = /[\W_]/.test(val);
        updateCriteriaStatus(critSymbol, hasSymbol);
        if (hasSymbol) score++;

        // Update Meter Bar & Colors
        let width = '0%';
        let color = '#E5E7EB';
        let text = 'Masukkan password';

        if (val.length === 0) {
            width = '0%';
            color = '#E5E7EB';
            text = 'Kekuatan password';
        } else if (score <= 1) {
            width = '20%';
            color = '#DC2626'; // Red
            text = 'Sangat Lemah ⚠️';
        } else if (score === 2) {
            width = '40%';
            color = '#EA580C'; // Orange-Red
            text = 'Lemah';
        } else if (score === 3) {
            width = '60%';
            color = '#D97706'; // Amber/Yellow
            text = 'Sedang (Cukup)';
        } else if (score === 4) {
            width = '80%';
            color = '#65A30D'; // Lime
            text = 'Kuat 👍';
        } else if (score === 5) {
            width = '100%';
            color = '#16A34A'; // Forest Green
            text = 'Sangat Kuat! ✨ (Sesuai Standar OWASP)';
        }

        meterBar.style.width = width;
        meterBar.style.backgroundColor = color;
        if (meterText) {
            meterText.textContent = text;
            meterText.style.color = color;
        }
    });

    function updateCriteriaStatus(el, isValid) {
        if (!el) return;
        if (isValid) {
            el.classList.add('is-valid');
            const icon = el.querySelector('.criteria-icon');
            if (icon) icon.textContent = '✓';
        } else {
            el.classList.remove('is-valid');
            const icon = el.querySelector('.criteria-icon');
            if (icon) icon.textContent = '○';
        }
    }
}

/* ==========================================================================
   5. LIVE SEARCH & FILTER (Peralatan / Catalog)
   ========================================================================== */
function initCatalogFilter() {
    const searchInput = document.getElementById('catalogSearch');
    const stockFilter = document.getElementById('catalogStockFilter');
    const sortSelect = document.getElementById('catalogSort');
    const cardsContainer = document.getElementById('catalogGrid');
    const countDisplay = document.getElementById('catalogFilterCount');
    const emptyState = document.getElementById('catalogEmptyState');

    if (!cardsContainer) return;

    const cards = Array.from(cardsContainer.querySelectorAll('.card-piknik'));

    function applyFilter() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const stockMode = stockFilter ? stockFilter.value : 'all';
        const sortMode = sortSelect ? sortSelect.value : 'default';

        let visibleCount = 0;

        cards.forEach(card => {
            const name = (card.getAttribute('data-name') || '').toLowerCase();
            const desc = (card.getAttribute('data-desc') || '').toLowerCase();
            const stock = parseInt(card.getAttribute('data-stock') || '0', 10);
            const status = card.getAttribute('data-status') || 'tersedia';

            // Match query
            const matchesQuery = !query || name.includes(query) || desc.includes(query);

            // Match stock
            let matchesStock = true;
            if (stockMode === 'available') {
                matchesStock = status === 'tersedia' && stock > 0;
            } else if (stockMode === 'empty') {
                matchesStock = status !== 'tersedia' || stock <= 0;
            }

            if (matchesQuery && matchesStock) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Apply sorting to visible cards
        if (sortMode !== 'default') {
            const sortedCards = [...cards].sort((a, b) => {
                const priceA = parseInt(a.getAttribute('data-price') || '0', 10);
                const priceB = parseInt(b.getAttribute('data-price') || '0', 10);
                const nameA = a.getAttribute('data-name') || '';
                const nameB = b.getAttribute('data-name') || '';

                if (sortMode === 'price-asc') return priceA - priceB;
                if (sortMode === 'price-desc') return priceB - priceA;
                if (sortMode === 'name-asc') return nameA.localeCompare(nameB);
                return 0;
            });

            sortedCards.forEach(c => cardsContainer.appendChild(c));
        }

        // Update count
        if (countDisplay) {
            countDisplay.textContent = `Menampilkan ${visibleCount} dari ${cards.length} paket`;
        }

        // Toggle empty state
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (stockFilter) stockFilter.addEventListener('change', applyFilter);
    if (sortSelect) sortSelect.addEventListener('change', applyFilter);
}

/* ==========================================================================
   6. CART DYNAMIC TOTAL CALCULATION
   ========================================================================== */
function initCartCalculator() {
    const checkboxes = document.querySelectorAll('.cart-checkbox');
    const totalDisplay = document.getElementById('totalHargaKeranjang');
    const selectAllCheckbox = document.getElementById('selectAllCart');

    if (!checkboxes.length || !totalDisplay) return;

    function hitungTotal() {
        let total = 0;
        let checkedCount = 0;

        checkboxes.forEach(cb => {
            if (cb.checked && !cb.disabled) {
                const harga = parseInt(cb.getAttribute('data-harga') || '0', 10);
                const jumlah = parseInt(cb.getAttribute('data-jumlah') || '1', 10);
                total += harga * jumlah;
                checkedCount++;
            }
        });

        totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');

        if (selectAllCheckbox) {
            const enabledBoxes = Array.from(checkboxes).filter(cb => !cb.disabled);
            selectAllCheckbox.checked = enabledBoxes.length > 0 && checkedCount === enabledBoxes.length;
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', hitungTotal);
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            const isChecked = selectAllCheckbox.checked;
            checkboxes.forEach(cb => {
                if (!cb.disabled) cb.checked = isChecked;
            });
            hitungTotal();
        });
    }

    hitungTotal();
}

/* ==========================================================================
   7. CHECKOUT CALCULATOR & INTERACTION
   ========================================================================== */
function initCheckoutCalculator() {
    const tglSewa = document.querySelector('[name="tanggal_sewa"]');
    const tglKembali = document.querySelector('[name="tanggal_kembali"]');
    const subtotalEl = document.getElementById('subtotalPerHari');
    const totalFinalInput = document.getElementById('totalFinal');
    const durasiInput = document.getElementById('durasiHari');
    const totalDisplay = document.getElementById('checkoutTotalHarga');
    const durasiDisplay = document.getElementById('checkoutDurasiInfo');
    const metodeSelect = document.getElementById('metodePembayaran');
    const buktiBox = document.getElementById('buktiBox');

    if (!tglSewa || !tglKembali || !subtotalEl) return;

    function calculateTotal() {
        const startVal = tglSewa.value;
        const endVal = tglKembali.value;
        const subtotal = parseInt(subtotalEl.value || '0', 10);

        if (!startVal || !endVal) return;

        const start = new Date(startVal);
        const end = new Date(endVal);
        const diffTime = end.getTime() - start.getTime();
        const durasi = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

        if (durasi <= 0) {
            if (durasiDisplay) {
                durasiDisplay.textContent = '⚠️ Tanggal kembali tidak boleh sebelum tanggal sewa!';
                durasiDisplay.style.color = 'var(--danger)';
            }
            return;
        }

        if (durasiDisplay) {
            durasiDisplay.textContent = `Durasi sewa: ${durasi} hari (${startVal} s/d ${endVal})`;
            durasiDisplay.style.color = 'var(--text-muted)';
        }

        const total = subtotal * durasi;
        if (totalDisplay) {
            totalDisplay.textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
        if (totalFinalInput) totalFinalInput.value = total;
        if (durasiInput) durasiInput.value = durasi;
    }

    tglSewa.addEventListener('change', calculateTotal);
    tglKembali.addEventListener('change', calculateTotal);

    if (metodeSelect && buktiBox) {
        metodeSelect.addEventListener('change', () => {
            if (metodeSelect.value === 'Bayar di Tempat') {
                buktiBox.style.display = 'none';
            } else {
                buktiBox.style.display = 'block';
            }
        });
    }

    calculateTotal();
}

/* ==========================================================================
   8. IMAGE PREVIEW (Payment Proof & Uploads)
   ========================================================================== */
function initImagePreview() {
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');

    fileInputs.forEach(input => {
        const targetId = input.getAttribute('data-preview');
        const previewContainer = document.getElementById(targetId);

        if (!previewContainer) return;

        input.addEventListener('change', () => {
            const file = input.files && input.files[0];
            if (!file) {
                previewContainer.classList.remove('has-image');
                previewContainer.innerHTML = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                alert('Silakan pilih file gambar dengan format JPG, JPEG, atau PNG.');
                input.value = '';
                previewContainer.classList.remove('has-image');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                previewContainer.classList.add('has-image');
                const fileSizeKb = Math.round(file.size / 1024);
                previewContainer.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="preview-thumb">
                    <div class="preview-meta">
                        <strong>${escapeHtml(file.name)}</strong>
                        <p>${fileSizeKb} KB - Siap diunggah</p>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        });
    });
}

/* ==========================================================================
   9. DASHBOARD CHART.JS (Zero Inline Scripts)
   ========================================================================== */
function initDashboardCharts() {
    const canvas = document.getElementById('chartHarian');
    if (!canvas || typeof window.Chart === 'undefined') return;

    try {
        const rawLabels = canvas.getAttribute('data-labels') || '[]';
        const rawValues = canvas.getAttribute('data-values') || '[]';
        const labels = JSON.parse(rawLabels);
        const dataValues = JSON.parse(rawValues);

        new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: dataValues,
                    borderColor: '#2D5A27',
                    backgroundColor: 'rgba(45, 90, 39, 0.1)',
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#2D5A27',
                    pointRadius: 3,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => 'Rp ' + Number(ctx.parsed.y).toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'Rp ' + Number(value).toLocaleString('id-ID')
                        },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    } catch (err) {
        console.error('Chart initialization error:', err);
    }
}

/* ==========================================================================
   10. SECURITY AUDIT LOGS FILTER (Audit Logs Page)
   ========================================================================== */
function initAuditLogFilter() {
    const filterSelect = document.getElementById('auditStatusFilter');
    const searchInput = document.getElementById('auditSearchInput');
    const tableBody = document.querySelector('#auditLogTable tbody');

    if (!tableBody) return;

    const rows = Array.from(tableBody.querySelectorAll('tr'));

    function applyAuditFilter() {
        const statusFilter = filterSelect ? filterSelect.value : 'ALL';
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        rows.forEach(row => {
            const rowStatus = (row.getAttribute('data-status') || '').toUpperCase();
            const rowAction = (row.getAttribute('data-action') || '').toLowerCase();
            const rowText = row.textContent.toLowerCase();

            let matchesStatus = true;
            if (statusFilter === 'SUCCESS') {
                matchesStatus = rowStatus === 'SUCCESS';
            } else if (statusFilter === 'FAILURE') {
                matchesStatus = rowStatus === 'FAILED';
            } else if (statusFilter === 'BLOCKED') {
                matchesStatus = row.getAttribute('data-blocked') === 'true' || rowAction.includes('blocked') || rowAction.includes('sqli') || rowAction.includes('idor');
            }

            const matchesQuery = !query || rowText.includes(query);

            if (matchesStatus && matchesQuery) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    if (filterSelect) filterSelect.addEventListener('change', applyAuditFilter);
    if (searchInput) searchInput.addEventListener('input', applyAuditFilter);
}

// Utility: Escape HTML in JS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* ==========================================================================
   11. DATA-CONFIRM DIALOG HANDLER (Zero Inline Onclick)
   ========================================================================== */
document.addEventListener('click', (e) => {
    const target = e.target.closest('[data-confirm]');
    if (target) {
        const msg = target.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        if (!confirm(msg)) {
            e.preventDefault();
            e.stopPropagation();
        }
    }
});

/* ==========================================================================
   12. ADMIN TRANSACTION FORM (Subtotal Calculation & Form Reset)
   ========================================================================== */
function initAdminTransactionForm() {
    const p = document.getElementById('id_peralatan');
    const j = document.getElementById('jumlah');
    const s = document.getElementById('tanggal_sewa');
    const k = document.getElementById('tanggal_kembali');
    const subtotalInput = document.getElementById('subtotal');
    const resetBtn = document.getElementById('btnResetTransaksi');

    if (!p || !j || !s || !k || !subtotalInput) return;

    function hitungSubtotal() {
        if (!p.value || !s.value || !k.value) return;
        const harga = parseInt(p.options[p.selectedIndex].dataset.harga || '0', 10);
        const jumlah = parseInt(j.value || '1', 10);
        const diff = new Date(k.value) - new Date(s.value);
        const hari = Math.max(1, Math.floor(diff / 86400000) + 1);
        subtotalInput.value = 'Rp ' + (harga * jumlah * hari).toLocaleString('id-ID');
    }

    [p, j, s, k].forEach(el => {
        el.addEventListener('change', hitungSubtotal);
        el.addEventListener('input', hitungSubtotal);
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            const form = document.getElementById('formTransaksi');
            if (form) form.reset();
            if (window.location.search.includes('edit=')) {
                window.location.href = 'index.php';
            }
        });
    }

    hitungSubtotal();
}

// Ensure initAdminTransactionForm is invoked
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminTransactionForm);
} else {
    initAdminTransactionForm();
}

/* ==========================================================================
   13. ADMIN EQUIPMENT FORM RESET
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    const resetPeralatanBtn = document.getElementById('btnResetPeralatan');
    if (resetPeralatanBtn) {
        resetPeralatanBtn.addEventListener('click', () => {
            const form = document.getElementById('formPeralatan');
            if (form) form.reset();
            if (window.location.search.includes('edit=')) {
                window.location.href = 'index.php';
            }
        });
    }
});

/* ==========================================================================
   14. ADMIN USER FORM RESET
   ========================================================================== */
document.addEventListener('DOMContentLoaded', () => {
    const resetUserBtn = document.getElementById('btnResetUser');
    if (resetUserBtn) {
        resetUserBtn.addEventListener('click', () => {
            const form = document.getElementById('formUser');
            if (form) form.reset();
            if (window.location.search.includes('edit=')) {
                window.location.href = 'index.php';
            }
        });
    }
});

/* ==========================================================================
   15. CAPTCHA REFRESH HANDLER (Zero Inline Code)
   ========================================================================== */
function initCaptchaRefresh() {
    const refreshBtns = document.querySelectorAll('.btn-refresh-captcha');
    refreshBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target') || 'captchaImg';
            const img = document.getElementById(targetId);
            if (img) {
                const currentSrc = img.src.split('?')[0];
                img.src = currentSrc + '?t=' + new Date().getTime();
            }
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCaptchaRefresh);
} else {
    initCaptchaRefresh();
}
