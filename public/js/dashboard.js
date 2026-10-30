document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | JADUAL PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    const params =
    new URLSearchParams(
        window.location.search
    );

    const success =
        params.get('success');

    if (success === 'payment_recorded') {

        Swal.fire({
            title: 'Bayaran Direkodkan!',
            text: 'Rekod belanja projek telah dikemaskini.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(function () {

            window.history.replaceState(
                {},
                document.title,
                window.location.pathname + (params.has('year') ? '?year=' + encodeURIComponent(params.get('year')) : '')
            );

        });

    }

    if (success === 'payment_cancelled') {

        Swal.fire({
            title: 'Bayaran Dibatalkan!',
            text: 'Rekod belanja telah dipadam dan tuntutan dikembalikan kepada status Menunggu.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(function () {

            window.history.replaceState(
                {},
                document.title,
                window.location.pathname + (params.has('year') ? '?year=' + encodeURIComponent(params.get('year')) : '')
            );

        });

    }

    if (success === 'schedule_deleted') {

        Swal.fire({
            title: 'Berjaya!',
            text: 'Jadual tuntutan telah dipadam.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(function () {

            const cleanUrl =
                new URL(window.location.href);

            cleanUrl.searchParams.delete('success');

            window.history.replaceState(
                {},
                document.title,
                cleanUrl.pathname + cleanUrl.search
            );

        });

    }


        if (success === 'schedule_added') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Jadual tuntutan telah ditambah.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

                window.history.replaceState(
                    {},
                    document.title,
                    window.location.pathname + (params.has('year') ? '?year=' + encodeURIComponent(params.get('year')) : '')
                );

            });

        }


        if (success === 'manual_expense_added') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Rekod belanja manual telah ditambah.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

                window.history.replaceState(
                    {},
                    document.title,
                    window.location.pathname + (params.has('year') ? '?year=' + encodeURIComponent(params.get('year')) : '')
                );

            });

        }

        if (success === 'variation_added') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Variasi kontrak telah ditambah.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

                const cleanUrl =
                    new URL(window.location.href);

                cleanUrl.searchParams.delete('success');

                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.pathname + cleanUrl.search
                );

            });

        }

        if (success === 'charge_added') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Caj pemajuan telah didaftarkan.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

        const cleanUrl =
            new URL(window.location.href);

                cleanUrl.searchParams.delete('success');

                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.pathname + cleanUrl.search
                );

            });

        }

        if (success === 'charge_updated') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Maklumat caj pemajuan telah dikemaskini.',
                icon: 'success',
                confirmButtonText: 'OK'
            });

        }


        if (success === 'charge_deleted') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Rekod caj pemajuan telah dipadam.',
                icon: 'success',
                confirmButtonText: 'OK'
            });

        }
        if (success === 'charge_schedule_generated') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Jadual bayaran telah dijana.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

                const cleanUrl =
                    new URL(window.location.href);

                cleanUrl.searchParams.delete('success');

                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.pathname + cleanUrl.search
                );

            });

        }

        if (success === 'notifications_cleared') {

            Swal.fire({
                title: 'Berjaya!',
                text: 'Semua notifikasi telah dikosongkan.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

                const cleanUrl =
                    new URL(window.location.href);

                cleanUrl.searchParams.delete('success');

                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.pathname + cleanUrl.search
                );

            });

        }

        if (
            success ===
            'charge_payment_recorded'
        ) {

            Swal.fire({
                title: 'Bayaran Direkodkan!',
                text: 'Bayaran caj pemajuan telah berjaya direkodkan.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(function () {

                const cleanUrl =
                    new URL(
                        window.location.href
                    );

                cleanUrl.searchParams.delete(
                    'success'
                );

                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.pathname +
                    cleanUrl.search
                );

            });

        }

        if (
            success ===
            'charge_payment_cancelled'
        ) {

            Swal.fire({
                title: 'Bayaran Dibatalkan!',
        text: 'Bayaran telah dibatalkan dan status dikembalikan kepada Menunggu.',
        icon: 'success',
        confirmButtonText: 'OK'
            }).then(function () {

                const cleanUrl =
                    new URL(
                        window.location.href
                    );

                cleanUrl.searchParams.delete(
                    'success'
                );

                window.history.replaceState(
                    {},
                    document.title,
                    cleanUrl.pathname +
                    cleanUrl.search
                );

            });

        }

        if (success === 'user_created') {

            Swal.fire({
                icon: 'success',
                title: 'Pengguna Berjaya Didaftarkan',
                text: 'Akaun pengguna baru telah diwujudkan.'
            });

        }


        if (success === 'user_updated') {

            Swal.fire({
                icon: 'success',
                title: 'Pengguna Dikemaskini',
                text: 'Maklumat pengguna berjaya dikemaskini.'
            });

        }


        if (success === 'user_deleted') {

            Swal.fire({
                icon: 'success',
                title: 'Pengguna Dipadam',
                text: 'Akaun pengguna telah dipadam.'
            });

        }

        const error =
    params.get('error');


if (error === 'user_email_exists') {

    Swal.fire({
        icon: 'error',
        title: 'Email Telah Digunakan',
        text: 'Sila gunakan alamat email yang lain.'
    });

}


if (error === 'user_password_short') {

    Swal.fire({
        icon: 'error',
        title: 'Kata Laluan Terlalu Pendek',
        text: 'Kata laluan mesti mempunyai sekurang-kurangnya 6 aksara.'
    });

}


if (error === 'cannot_delete_self') {

    Swal.fire({
        icon: 'warning',
        title: 'Tidak Dibenarkan',
        text: 'Anda tidak boleh memadam akaun sendiri.'
    });

}

    if (window.ChargeInstallmentsPreview) { window.ChargeInstallmentsPreview.mount(document); }

        document
        .querySelectorAll('.clear-notifications-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    Swal.fire({
                        title: 'Kosongkan Semua Notifikasi?',
                        text: 'Semua sejarah notifikasi akan dipadam secara kekal.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Kosongkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc3545'
                    }).then(function (result) {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });

                }
            );

        });

        document
        .querySelectorAll('.delete-charge-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    Swal.fire({
                        title: 'Padam Caj Pemajuan?',
                        text: 'Rekod caj pemajuan ini akan dipadam secara kekal.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Padam',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc3545'
                    }).then(function (result) {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });

                }
            );

        });

        document
        .querySelectorAll('.edit-charge-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    const currentForm = form;

                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Maklumat caj pemajuan akan dikemaskini.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {

                        if (result.isConfirmed) {
                            currentForm.submit();
                        }

                    });

                }
            );

        });

        const addChargeForm =
            document.getElementById('addChargeForm');

        if (addChargeForm) {

            addChargeForm.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    Swal.fire({
                        title: 'Daftar Caj Pemajuan?',
                        text: 'Pastikan maklumat caj telah diisi dengan betul.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Daftar',
                        cancelButtonText: 'Semak Semula'
                    }).then(function (result) {

                        if (result.isConfirmed) {
                            addChargeForm.submit();
                        }

                    });

                }
            );

        }

    window.addScheduleRow = function () {

        const container =
            document.getElementById('scheduleContainer');

        const paymentMethodElement =
            document.getElementById('paymentMethod');

        if (!container || !paymentMethodElement) {
            return;
        }

        const paymentMethod =
            paymentMethodElement.value;

        const row =
            document.createElement('div');

        row.className =
            'schedule-row border rounded bg-white p-3 mb-2';

        row.innerHTML = `
            <div class="row g-2">

                <div class="col-md-4">

                    <label class="form-label">
                        Tarikh Tuntutan
                    </label>

                    <input
                        type="date"
                        name="schedule_date[]"
                        class="form-control"
                    >

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Jumlah (RM)
                    </label>

                    <input
                        type="number"
                        name="schedule_amount[]"
                        class="form-control"
                        step="0.01"
                        min="0"
                    >

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Jenis
                    </label>

                    <select
                        name="schedule_type[]"
                        class="form-select schedule-type"
                    >

                        <option value="${paymentMethod}">
                            ${paymentMethod}
                        </option>

                    </select>

                </div>

                <div class="col-md-1 d-flex align-items-end">

                    <button
                        type="button"
                        class="btn btn-danger"
                        onclick="this.closest('.schedule-row').remove()"
                    >
                        ×
                    </button>

                </div>

            </div>
        `;

        container.appendChild(row);
    };


    /*
    |--------------------------------------------------------------------------
    | KAEDAH PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    const paymentMethod =
        document.getElementById('paymentMethod');

    if (paymentMethod) {

        paymentMethod.addEventListener(
            'change',
            function () {

                document
                    .querySelectorAll('.schedule-type')
                    .forEach(function (select) {

                        select.innerHTML = `
                            <option value="${paymentMethod.value}">
                                ${paymentMethod.value}
                            </option>
                        `;

                    });

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | PADAM PROJEK
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.delete-project-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    Swal.fire({
                        title: 'Padam Projek?',
                        text: 'Rekod projek ini akan dipadam secara kekal.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Padam',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc3545'
                    }).then(function (result) {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });

                }
            );

        });


    /*
    |--------------------------------------------------------------------------
    | TAMBAH PROJEK
    |--------------------------------------------------------------------------
    */

    const addProjectForm =
        document.getElementById('addProjectForm');

    if (addProjectForm) {
        let confirmingProject = false;
        addProjectForm.addEventListener('submit', function (event) {
            // The ordinary POST form remains usable when the confirmation CDN is unavailable.
            if (typeof Swal === 'undefined') {
                if (!window.confirm('Tambah Projek? Pastikan semua maklumat telah diisi dengan betul.')) {
                    event.preventDefault();
                }
                return;
            }
            event.preventDefault();
            if (confirmingProject) { return; }
            confirmingProject = true;
            Swal.fire({
                title: 'Tambah Projek?',
                text: 'Pastikan semua maklumat projek telah diisi dengan betul.',
                icon: 'question', showCancelButton: true,
                confirmButtonText: 'Ya, Tambah', cancelButtonText: 'Semak Semula'
            }).then(function (result) {
                confirmingProject = false;
                if (result.isConfirmed) { addProjectForm.submit(); }
            }).catch(function () { confirmingProject = false; });
        });
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT PROJEK
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.edit-project-form')
        .forEach(function (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    event.preventDefault();

                    Swal.fire({
                        title: 'Simpan Perubahan?',
                        text: 'Maklumat projek akan dikemaskini.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });

                }
            );

        });

        document
    .querySelectorAll(
        '.cancel-charge-payment-form'
    )
    .forEach(function (form) {

        form.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();


                Swal.fire({
                    title: 'Batalkan Bayaran?',
                    text: 'Status bayaran akan dikembalikan kepada Menunggu dan resit akan dipadam.',
                    icon: 'warning',
                    showCancelButton: true,

                    confirmButtonText:
                        'Ya, Batalkan',

                    cancelButtonText:
                        'Tidak',

                    confirmButtonColor:
                        '#d63939'

                }).then(function (result) {

                    if (result.isConfirmed) {
                        form.submit();
                    }

                });

            }
        );

    });

    document
    .querySelectorAll('.edit-payment-plan')
    .forEach(function (select) {

        select.addEventListener(
            'change',
            function () {

                const form =
                    select.closest('form');

                if (!form) {
                    return;
                }

                const container =
                    form.querySelector(
                        '.edit-installment-container'
                    );

                const chargeAmountInput =
                    form.querySelector(
                        'input[name="charge_amount"]'
                    );

                if (
                    !container ||
                    !chargeAmountInput
                ) {
                    return;
                }


                const plan =
                    select.value;

                const total =
                    parseFloat(
                        chargeAmountInput.value
                    ) || 0;


                let count = 1;


                if (plan === '3 Ansuran') {
                    count = 3;
                }


                if (plan === '4 Ansuran') {
                    count = 4;
                }


                const installmentAmount =
                    count > 0
                    ? total / count
                    : total;


                container.innerHTML = '';


                for (
                    let i = 1;
                    i <= count;
                    i++
                ) {

                    const row =
                        document.createElement('div');


                    row.className =
                        'border rounded p-3 mb-3';


                    row.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-3">

                            <div class="fw-bold">
                                Ansuran #${i}
                            </div>

                            <span class="status-badge status-pending">
                                Menunggu
                            </span>

                        </div>


                        <input
                            type="hidden"
                            name="new_installment_no[]"
                            value="${i}"
                        >


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Jumlah (RM)
                                </label>

                                <input
                                    type="number"
                                    name="new_installment_amount[]"
                                    class="form-control"
                                    min="0.01"
                                    step="0.01"
                                    value="${installmentAmount.toFixed(2)}"
                                    required
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Tarikh Akhir
                                </label>

                                <input
                                    type="date"
                                    name="new_installment_due_date[]"
                                    class="form-control"
                                    required
                                >

                            </div>

                        </div>
                    `;


                    container.appendChild(row);

                }

            }
        );

    });

    document.querySelectorAll('.edit-charge-form[data-old-input]').forEach(function (form) {
        const oldInput = JSON.parse(form.dataset.oldInput);
        const select = form.querySelector('.edit-payment-plan');
        if (!select || !Array.isArray(oldInput.new_installment_no)) { return; }
        select.dispatchEvent(new Event('change'));
        ['new_installment_amount', 'new_installment_due_date'].forEach(function (name) {
            if (!Array.isArray(oldInput[name])) { return; }
            form.querySelectorAll('[name="' + name + '[]"]').forEach(function (input, index) {
                if (typeof oldInput[name][index] === 'string') { input.value = oldInput[name][index]; }
            });
        });
    });

    /*
|--------------------------------------------------------------------------
| CREATE USER
|--------------------------------------------------------------------------
*/

const addUserForm =
    document.getElementById(
        'addUserForm'
    );


if (addUserForm) {

    addUserForm.addEventListener(
        'submit',
        function (event) {

            event.preventDefault();


            Swal.fire({
                title:
                    'Daftar Pengguna?',

                text:
                    'Pastikan maklumat pengguna telah diisi dengan betul.',

                icon:
                    'question',

                showCancelButton:
                    true,

                confirmButtonText:
                    'Ya, Daftar',

                cancelButtonText:
                    'Semak Semula'

            }).then(
                function (result) {

                    if (
                        result.isConfirmed
                    ) {
                        addUserForm.submit();
                    }

                }
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| EDIT USER
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        '.edit-user-form'
    )
    .forEach(function (form) {

        form.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();


                Swal.fire({
                    title:
                        'Simpan Perubahan?',

                    text:
                        'Maklumat pengguna akan dikemaskini.',

                    icon:
                        'question',

                    showCancelButton:
                        true,

                    confirmButtonText:
                        'Ya, Simpan',

                    cancelButtonText:
                        'Batal'

                }).then(
                    function (result) {

                        if (
                            result.isConfirmed
                        ) {
                            form.submit();
                        }

                    }
                );

            }
        );

    });



/*
|--------------------------------------------------------------------------
| DELETE USER
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        '.delete-user-form'
    )
    .forEach(function (form) {

        form.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();


                Swal.fire({
                    title:
                        'Padam Pengguna?',

                    text:
                        'Pengguna ini akan dipadam secara kekal.',

                    icon:
                        'warning',

                    showCancelButton:
                        true,

                    confirmButtonText:
                        'Ya, Padam',

                    cancelButtonText:
                        'Batal',

                    confirmButtonColor:
                        '#d63939'

                }).then(
                    function (result) {

                        if (
                            result.isConfirmed
                        ) {
                            form.submit();
                        }

                    }
                );

            }
        );

    });

    /*
|--------------------------------------------------------------------------
| SIDEBAR TOGGLE
|--------------------------------------------------------------------------
*/

const sidebarToggle =
    document.getElementById(
        'sidebarToggle'
    );


if (sidebarToggle) {

    /*
    |--------------------------------------------------------------------------
    | RESTORE PREVIOUS STATE
    |--------------------------------------------------------------------------
    */

    const savedSidebarState =
        localStorage.getItem(
            'sidebarCollapsed'
        );


    if (
        savedSidebarState === 'true'
    ) {

        document.body
            .classList
            .add(
                'sidebar-collapsed'
            );

    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE SIDEBAR
    |--------------------------------------------------------------------------
    */

    sidebarToggle.addEventListener(
        'click',
        function () {

            document.body
                .classList
                .toggle(
                    'sidebar-collapsed'
                );


            const isCollapsed =
                document.body
                    .classList
                    .contains(
                        'sidebar-collapsed'
                    );


            localStorage.setItem(
                'sidebarCollapsed',
                isCollapsed
            );

        }
    );

}

/*
|--------------------------------------------------------------------------
| PRINT DEVELOPMENT CHARGE REPORT
|--------------------------------------------------------------------------
*/

const printChargeReport =
    document.getElementById(
        'printChargeReport'
    );


if (printChargeReport) {

    printChargeReport.addEventListener(
        'click',
        function () {

            const yearSelect =
                document.querySelector(
                    'select[name="year"]'
                );


            const statusSelect =
                document.getElementById(
                    'chargeStatusFilter'
                );


            const year =
                yearSelect
                ? yearSelect.value
                : document.querySelector('meta[name="reporting-year"]').content;


            const status =
                statusSelect
                ? statusSelect.value
                : '';


            const url =
                window.APP_ROOT + 'report/charges' +
                '?year=' +
                encodeURIComponent(year) +
                '&status=' +
                encodeURIComponent(status);


            window.open(
                url,
                '_blank'
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| PRINT PROJECT REPORT
|--------------------------------------------------------------------------
*/

const printProjectReport =
    document.getElementById(
        'printProjectReport'
    );


if (printProjectReport) {

    printProjectReport.addEventListener(
        'click',
        function () {

            const yearSelect =
                document.querySelector(
                    'select[name="year"]'
                );


            const departmentSelect =
                document.getElementById(
                    'departmentFilter'
                );


            const projectStatusSelect =
                document.getElementById(
                    'projectStatusFilter'
                );


            const paymentStatusSelect =
                document.getElementById(
                    'projectPaymentFilter'
                );


            const year =
                yearSelect
                ? yearSelect.value
                : document.querySelector('meta[name="reporting-year"]').content;


            const department =
                departmentSelect
                ? departmentSelect.value
                : '';


            const projectStatus =
                projectStatusSelect
                ? projectStatusSelect.value
                : '';


            const paymentStatus =
                paymentStatusSelect
                ? paymentStatusSelect.value
                : '';


            const url =
                window.APP_ROOT + 'report/projects' +
                '?year=' +
                encodeURIComponent(year) +
                '&department=' +
                encodeURIComponent(department) +
                '&project_status=' +
                encodeURIComponent(projectStatus) +
                '&payment_status=' +
                encodeURIComponent(paymentStatus);


            window.open(
                url,
                '_blank'
            );

        }
    );

}
});
