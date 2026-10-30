const assert = require('assert');
const fs = require('fs');
const calculator = require('../public/js/charge-installments.js');
const cases = JSON.parse(fs.readFileSync(__dirname + '/fixtures/problem4-installments.json', 'utf8'));
let checks = 0;
function check(ok, message) { assert(ok, message); checks++; }
cases.forEach(test => {
    const actual = calculator.calculate(test.amount, '2024-01-31');
    check(JSON.stringify(actual.amounts) === JSON.stringify(test.amounts) && actual.interest === test.interest, 'Preview principal/interest ' + test.amount);
    check(actual.amounts.reduce((sum, amount) => sum + calculator.cents(amount), 0) === calculator.cents(test.amount), 'Exact preview sum');
});
[['2024-01-31','2024-04-30','2024-07-30'],['2023-08-31','2023-11-30','2024-02-29'],
 ['2022-08-31','2022-11-30','2023-02-28'],['2024-02-29','2024-05-29','2024-08-29'],
 ['2099-11-30','2100-02-28','2100-05-28']].forEach(dates => {
    check(JSON.stringify(calculator.calculate('150000', dates[0]).dates) === JSON.stringify(dates), 'Preview calendar clamp');
});
['99999.99','0','-1','1e6','150000.001','10000000000'].forEach(value => { assert.throws(() => calculator.principal(value)); checks++; });
['','2023-02-29','2024-02-30','2024-1-31','0000-01-01','9999-07-31'].forEach(date => { assert.throws(() => calculator.calculate('150000', date)); checks++; });
// Exercise the actual form mount/listeners with small DOM stubs, without CDN dependencies.
function input(value = '') {
    return {value, listeners: {}, validity: '', addEventListener(event, fn) { this.listeners[event] = fn; },
        setCustomValidity(message) { this.validity = message; }};
}
const plan = input('3 Ansuran'), amount = input('150000.00'), first = input('2024-01-31');
const section = {style: {}}, controls = {}, estimate = {}, error = {};
const rows = {dataset: {oldInput: '{}'}, children: [], set innerHTML(value) { this.children = []; },
    appendChild(row) { this.children.push(row); }, querySelectorAll(selector) {
        return this.children.map(row => row.inputs[selector.includes('amount') ? 0 : 1]);
    }};
const nodes = {chargePaymentPlan: plan, chargeInstallmentSection: section, chargeInstallmentRows: rows,
    chargeAutomaticControls: controls, chargeFirstDeadline: first, chargeInterestEstimate: estimate, chargeInstallmentError: error};
const document = {getElementById: name => nodes[name], querySelector: () => amount,
    createElement: () => ({inputs: [input(), input()], set innerHTML(html) { this.html = html; }})};
calculator.mount(document);
check(rows.children.length === 3 && rows.children.every(row => row.html.includes('readonly')), 'Three readonly rows');
check(rows.children[1].inputs[0].value === '25000.00' && rows.children[2].inputs[1].value === '2024-07-30', 'Mounted initial preview');
amount.value = '300000.03'; amount.listeners.input();
check(rows.children[2].inputs[0].value === '75000.00', 'Amount input recalculates');
first.value = '2023-08-31'; first.listeners.change();
check(rows.children[2].inputs[1].value === '2024-02-29', 'Deadline change recalculates');
amount.value = '99999.99'; amount.listeners.input();
check(amount.validity.includes('Bayaran Penuh') && error.textContent.includes('RM100,000'), 'Below-threshold Malay validation');
plan.value = '4 Ansuran'; plan.listeners.change();
check(rows.children.length === 4 && rows.children.every(row => !row.html.includes('readonly')) && first.disabled && !amount.validity, 'Legacy four manual rows');
plan.value = 'Bayaran Penuh'; plan.listeners.change();
check(rows.children.length === 0 && section.style.display === 'none' && first.disabled, 'Full-payment form unchanged');
console.log('PASS ' + checks + ' automatic installment JavaScript assertions');
