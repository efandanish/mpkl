/* Preview only. PHP ChargeInstallments recalculates and validates on submission. */
(function (root) {
    'use strict';
    const minimumMessage = 'Pelan 3 Ansuran memerlukan Jumlah Caj sekurang-kurangnya RM100,000.00. Sila pilih Bayaran Penuh bagi jumlah yang lebih rendah.';
    function cents(value) {
        const match = /^(\d{1,10})(?:\.(\d{1,2}))?$/.exec(String(value));
        if (!match || match[0] !== String(value)) { throw new Error('Jumlah Caj mesti nombor sah dengan maksimum 2 tempat perpuluhan.'); }
        return Number(match[1]) * 100 + Number((match[2] || '').padEnd(2, '0'));
    }
    function money(value) {
        return Math.floor(value / 100) + '.' + String(value % 100).padStart(2, '0');
    }
    function principal(value) {
        const total = cents(value);
        if (total < 10000000) { throw new Error(minimumMessage); }
        const first = total <= 20000000 ? 10000000 : Math.floor((total + 1) / 2);
        const second = total <= 20000000 ? Math.floor((total - first + 1) / 2) : Math.floor((total + 2) / 4);
        return {amounts: [first, second, total - first - second].map(money),
            interest: money(Math.floor(((total - first) * 8 + 50) / 100))};
    }
    function days(year, month) {
        if (month === 2) { return year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0) ? 29 : 28; }
        return [4, 6, 9, 11].includes(month) ? 30 : 31;
    }
    function addThreeMonths(value) {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
        if (!match || match[0] !== String(value) || Number(match[1]) < 1000 || Number(match[2]) < 1 || Number(match[2]) > 12
            || Number(match[3]) < 1 || Number(match[3]) > days(Number(match[1]), Number(match[2]))) {
            throw new Error('Tarikh Akhir Ansuran 1 wajib diisi dengan tarikh kalendar sah (YYYY-MM-DD).');
        }
        const monthIndex = Number(match[1]) * 12 + Number(match[2]) - 1 + 3;
        const year = Math.floor(monthIndex / 12);
        const month = monthIndex % 12 + 1;
        if (year > 9999) { throw new Error('Tarikh ansuran terjana mesti dalam julat tahun 1000 hingga 9999.'); }
        return year + '-' + String(month).padStart(2, '0') + '-' + String(Math.min(Number(match[3]), days(year, month))).padStart(2, '0');
    }
    function calculate(value, first) {
        const result = principal(value);
        const second = addThreeMonths(first);
        result.dates = [first, second, addThreeMonths(second)];
        return result;
    }
    function mount(document) {
        const plan = document.getElementById('chargePaymentPlan');
        const section = document.getElementById('chargeInstallmentSection');
        const rows = document.getElementById('chargeInstallmentRows');
        const controls = document.getElementById('chargeAutomaticControls');
        const firstDate = document.getElementById('chargeFirstDeadline');
        const amount = document.querySelector('#addChargeForm [name="charge_amount"]');
        const estimate = document.getElementById('chargeInterestEstimate');
        const error = document.getElementById('chargeInstallmentError');
        if (!plan || !section || !rows || !controls || !firstDate || !amount) { return; }
        let old = {};
        try { old = JSON.parse(rows.dataset.oldInput || '{}'); } catch (ignored) { /* Backend remains authoritative. */ }
        function preview() {
            if (plan.value !== '3 Ansuran') { return; }
            amount.setCustomValidity(''); firstDate.setCustomValidity('');
            error.textContent = ''; estimate.textContent = '—';
            const amounts = rows.querySelectorAll('[name="installment_amount[]"]');
            const dates = rows.querySelectorAll('[name="installment_due_date[]"]');
            amounts.forEach(input => { input.value = ''; });
            dates.forEach(input => { input.value = ''; });
            try {
                const result = principal(amount.value);
                amounts.forEach((input, index) => { input.value = result.amounts[index]; });
                estimate.textContent = 'RM ' + result.interest;
            } catch (failure) {
                error.textContent = failure.message;
                amount.setCustomValidity(failure.message);
                return;
            }
            if (!firstDate.value) { return; }
            try {
                const result = calculate(amount.value, firstDate.value);
                dates.forEach((input, index) => { input.value = result.dates[index]; });
            } catch (failure) {
                error.textContent = failure.message;
                firstDate.setCustomValidity(failure.message);
            }
        }
        function render(restore) {
            const automatic = plan.value === '3 Ansuran';
            const count = automatic ? 3 : (plan.value === '4 Ansuran' ? 4 : 0);
            section.style.display = count ? 'block' : 'none';
            controls.hidden = !automatic;
            firstDate.disabled = !automatic;
            firstDate.required = automatic;
            amount.setCustomValidity(''); firstDate.setCustomValidity('');
            rows.innerHTML = '';
            for (let index = 0; index < count; index++) {
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-end mb-2';
                row.innerHTML = '<div class="col-md-2"><label class="form-label">Ansuran #' + (index + 1) + '</label>' +
                    '<input type="hidden" name="installment_no[]" value="' + (index + 1) + '"></div>' +
                    '<div class="col-md-5"><label class="form-label">Jumlah (RM)</label>' +
                    '<input type="number" name="installment_amount[]" class="form-control" step="0.01" min="0" required ' + (automatic ? 'readonly' : '') + '></div>' +
                    '<div class="col-md-5"><label class="form-label">Tarikh Akhir</label>' +
                    '<input type="date" name="installment_due_date[]" class="form-control" required ' + (automatic ? 'readonly' : '') + '></div>';
                rows.appendChild(row);
            }
            if (automatic) { preview(); }
            else if (restore) {
                ['installment_amount', 'installment_due_date'].forEach(name => {
                    const values = old[name];
                    if (Array.isArray(values)) {
                        rows.querySelectorAll('[name="' + name + '[]"]').forEach((input, index) => {
                            if (typeof values[index] === 'string') { input.value = values[index]; }
                        });
                    }
                });
            }
        }
        plan.addEventListener('change', () => render(false));
        amount.addEventListener('input', preview);
        firstDate.addEventListener('input', preview);
        firstDate.addEventListener('change', preview);
        render(true);
    }
    const api = {cents, money, principal, addThreeMonths, calculate, mount};
    if (typeof module !== 'undefined' && module.exports) { module.exports = api; }
    else { root.ChargeInstallmentsPreview = api; }
})(typeof window === 'undefined' ? globalThis : window);
