// Exercise the actual project-create submit handler with DOM stubs; no browser/CDN dependency.
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');
const source = fs.readFileSync(__dirname + '/../public/js/dashboard.js', 'utf8');
const start = source.indexOf('    const addProjectForm =');
const end = source.indexOf('    /*', start);
assert(start >= 0 && end > start, 'Project submit handler exists');
const handlerSource = source.slice(start, end);

function fixture(confirm, swal) {
    let handler;
    let submitted = 0;
    const form = {
        addEventListener: (event, callback) => { assert.strictEqual(event, 'submit'); handler = callback; },
        submit: () => { submitted++; }
    };
    const context = {document: {getElementById: () => form}, window: {confirm: () => confirm}};
    if (swal) { context.Swal = swal; }
    vm.runInNewContext(handlerSource, context);
    return {
        submit: () => { let prevented = false; handler({preventDefault: () => { prevented = true; }}); return prevented; },
        count: () => submitted
    };
}

(async function () {
    assert.strictEqual(fixture(true).submit(), false, 'Without Swal, native confirm permits ordinary POST');
    assert.strictEqual(fixture(false).submit(), true, 'Native confirm cancellation prevents POST');
    let resolve;
    let dialogs = 0;
    const pending = fixture(true, {fire: () => { dialogs++; return new Promise(done => { resolve = done; }); }});
    assert.strictEqual(pending.submit(), true);
    pending.submit();
    assert.strictEqual(dialogs, 1, 'Repeated submit while confirmation open does not duplicate dialogs');
    resolve({isConfirmed: true});
    await Promise.resolve();
    assert.strictEqual(pending.count(), 1, 'Confirmed request submits once');
    const cancelled = fixture(true, {fire: () => Promise.resolve({isConfirmed: false})});
    cancelled.submit();
    await Promise.resolve();
    assert.strictEqual(cancelled.count(), 0, 'Cancelled confirmation does not submit');
    console.log('PASS Project confirmation fallback, cancellation and repeated-submit checks');
})().catch(error => { console.error(error); process.exitCode = 1; });
