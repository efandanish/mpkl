document.addEventListener('DOMContentLoaded', function () {
    function addCsrfToken(form) {
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = 'csrf_token';
        token.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(token);
        const year = document.createElement('input');
        year.type = 'hidden';
        year.name = 'year';
        year.value = document.querySelector('meta[name="reporting-year"]').content;
        form.appendChild(year);
    }


    document
        .querySelectorAll('.record-payment-btn')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const scheduleId =
                        this.dataset.scheduleId;

                    const amount =
                        this.dataset.amount;

                    const claimDate =
                        this.dataset.date;

                    Swal.fire({

                        title: 'Rekod Bayaran',

                        html: `
                            <div class="text-start">

                                <label class="form-label">
                                    Jumlah Bayaran (RM)
                                </label>

                                <input
                                    id="swal-payment-amount"
                                    type="number"
                                    class="form-control mb-3"
                                    step="0.01"
                                    min="0"
                                    value="${amount}"
                                >

                                <label class="form-label">
                                    Tarikh Bayaran
                                </label>

                                <input
                                    id="swal-payment-date"
                                    type="date"
                                    class="form-control mb-3"
                                    value="${claimDate}"
                                >

                                <label class="form-label">
                                    Catatan
                                </label>

                                <textarea
                                    id="swal-payment-description"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Contoh: Bayaran tuntutan kontraktor"
                                ></textarea>

                            </div>
                        `,

                        icon: 'info',

                        showCancelButton: true,

                        confirmButtonText:
                            'Rekod Bayaran',

                        cancelButtonText:
                            'Batal',

                        focusConfirm: false,

                        preConfirm: function () {

                            const paymentAmount =
                                document
                                    .getElementById(
                                        'swal-payment-amount'
                                    )
                                    .value;

                            const paymentDate =
                                document
                                    .getElementById(
                                        'swal-payment-date'
                                    )
                                    .value;

                            const description =
                                document
                                    .getElementById(
                                        'swal-payment-description'
                                    )
                                    .value;

                            if (
                                !paymentAmount ||
                                Number(paymentAmount) <= 0
                            ) {

                                Swal.showValidationMessage(
                                    'Masukkan jumlah bayaran.'
                                );

                                return false;
                            }

                            if (!paymentDate) {

                                Swal.showValidationMessage(
                                    'Pilih tarikh bayaran.'
                                );

                                return false;
                            }

                            return {
                                amount:
                                    paymentAmount,

                                paymentDate:
                                    paymentDate,

                                description:
                                    description
                            };

                        }

                    }).then(function (result) {

                        if (!result.isConfirmed) {
                            return;
                        }

                        const form =
                            document.createElement(
                                'form'
                            );

                        form.method = 'POST';
                addCsrfToken(form);

                        form.action =
                            window.APP_ROOT + 'project/recordPayment/' +
                            scheduleId;


                        const amountInput =
                            document.createElement(
                                'input'
                            );

                        amountInput.type =
                            'hidden';

                        amountInput.name =
                            'amount';

                        amountInput.value =
                            result.value.amount;


                        const dateInput =
                            document.createElement(
                                'input'
                            );

                        dateInput.type =
                            'hidden';

                        dateInput.name =
                            'payment_date';

                        dateInput.value =
                            result.value.paymentDate;


                        const descriptionInput =
                            document.createElement(
                                'input'
                            );

                        descriptionInput.type =
                            'hidden';

                        descriptionInput.name =
                            'description';

                        descriptionInput.value =
                            result.value.description;


                        form.appendChild(
                            amountInput
                        );

                        form.appendChild(
                            dateInput
                        );

                        form.appendChild(
                            descriptionInput
                        );

                        document.body.appendChild(
                            form
                        );

                        form.submit();

                    });

                }
            );

        });

        document
    .querySelectorAll('.expense-delete-btn')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const expenseId = this.dataset.expenseId;

            Swal.fire({
                title: 'Batalkan Bayaran?',
                text: 'Rekod belanja ini akan dipadam dan status tuntutan akan kembali kepada Menunggu.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Batalkan',
                cancelButtonText: 'Tutup',
                confirmButtonColor: '#dc3545'
            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                addCsrfToken(form);
                form.action =
                    window.APP_ROOT + 'project/cancelPayment/' + expenseId;

                document.body.appendChild(form);
                form.submit();
            });

        });

    });

    document
    .querySelectorAll('.add-schedule-btn')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const projectId =
                this.dataset.projectId;

            Swal.fire({
                title: 'Tambah Jadual Tuntutan',

                html: `
                    <div class="text-start">

                        <label class="form-label">
                            Tarikh Tuntutan
                        </label>

                        <input
                            id="new-schedule-date"
                            type="date"
                            class="form-control mb-3"
                        >

                        <label class="form-label">
                            Jumlah (RM)
                        </label>

                        <input
                            id="new-schedule-amount"
                            type="text"
                            inputmode="decimal"
                            class="form-control mb-3"
                            placeholder="Contoh: 150000 atau 150,000"
                        >

                        <label class="form-label">
                            Jenis
                        </label>

                        <select
                            id="new-schedule-type"
                            class="form-select"
                        >
                            <option value="One-off">
                                One-off
                            </option>

                            <option value="Ansuran" selected>
                                Ansuran
                            </option>

                            <option value="Pajakan/Sewa">
                                Pajakan/Sewa
                            </option>
                        </select>

                    </div>
                `,

                showCancelButton: true,
                confirmButtonText: 'Tambah',
                cancelButtonText: 'Batal',

                preConfirm: function () {

                    const date =
                        document.getElementById(
                            'new-schedule-date'
                        ).value;

                    const amountInput =
                        document.getElementById(
                            'new-schedule-amount'
                        ).value.trim();

                    const cleanAmount =
                        amountInput.replace(/,/g, '');

                    const amount =
                        parseFloat(cleanAmount);

                    const type =
                        document.getElementById(
                            'new-schedule-type'
                        ).value;

                    if (!date) {
                        Swal.showValidationMessage(
                            'Pilih tarikh tuntutan.'
                        );

                        return false;
                    }

                    if (
                        isNaN(amount) ||
                        amount <= 0
                    ) {

                        Swal.showValidationMessage(
                            'Masukkan jumlah yang sah.'
                        );

                        return false;
                    }

                    return {
                        date: date,
                        amount: amount.toFixed(2),
                        type: type
                    };
                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form =
                    document.createElement('form');

                form.method = 'POST';
                addCsrfToken(form);

                form.action =
                    window.APP_ROOT + 'project/addScheduleRecord/' +
                    projectId;

                const values = {
                    claim_date: result.value.date,
                    amount: result.value.amount,
                    type: result.value.type
                };

                Object.keys(values).forEach(function (name) {

                    const input =
                        document.createElement('input');

                    input.type = 'hidden';
                    input.name = name;
                    input.value = values[name];

                    form.appendChild(input);

                });

                document.body.appendChild(form);

                form.submit();

            });

        });

    });

    document
    .querySelectorAll('.manual-expense-btn')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const projectId =
                this.dataset.projectId;

            Swal.fire({
                title: 'Rekod Belanja Manual',

                html: `
                    <div class="text-start">

                        <label class="form-label">
                            Jumlah Bayaran (RM)
                        </label>

                        <input
                            id="manual-expense-amount"
                            type="number"
                            class="form-control mb-3"
                            min="0"
                            step="0.01"
                        >

                        <label class="form-label">
                            Tarikh Bayaran
                        </label>

                        <input
                            id="manual-expense-date"
                            type="date"
                            class="form-control mb-3"
                        >

                        <label class="form-label">
                            Catatan
                        </label>

                        <textarea
                            id="manual-expense-description"
                            class="form-control"
                            rows="3"
                            placeholder="Contoh: Bayaran tambahan kontraktor"
                        ></textarea>

                    </div>
                `,

                showCancelButton: true,
                confirmButtonText: 'Rekod',
                cancelButtonText: 'Batal',

                preConfirm: function () {

                    const amount =
                        document.getElementById(
                            'manual-expense-amount'
                        ).value;

                    const date =
                        document.getElementById(
                            'manual-expense-date'
                        ).value;

                    const description =
                        document.getElementById(
                            'manual-expense-description'
                        ).value;

                    if (!amount || Number(amount) <= 0) {

                        Swal.showValidationMessage(
                            'Masukkan jumlah bayaran.'
                        );

                        return false;
                    }

                    if (!date) {

                        Swal.showValidationMessage(
                            'Pilih tarikh bayaran.'
                        );

                        return false;
                    }

                    return {
                        amount: amount,
                        date: date,
                        description: description
                    };
                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form =
                    document.createElement('form');

                form.method = 'POST';
                addCsrfToken(form);

                form.action =
                    window.APP_ROOT + 'project/addManualExpense/' +
                    projectId;


                const values = {
                    amount: result.value.amount,
                    payment_date: result.value.date,
                    description: result.value.description
                };


                Object.keys(values).forEach(function (name) {

                    const input =
                        document.createElement('input');

                    input.type = 'hidden';
                    input.name = name;
                    input.value = values[name];

                    form.appendChild(input);

                });


                document.body.appendChild(form);

                form.submit();

            });

        });

    });

    document
    .querySelectorAll('.print-project-btn')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            window.print();

        });

    });

    document
    .querySelectorAll('.add-variation-btn')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const projectId =
                this.dataset.projectId;

            Swal.fire({
                title: 'Tambah Variasi Kontrak',

                html: `
                    <div class="text-start">

                        <label class="form-label">
                            Penerangan
                        </label>

                        <input
                            id="variation-description"
                            type="text"
                            class="form-control mb-3"
                            placeholder="Contoh: Kerja tambahan saliran"
                        >

                        <label class="form-label">
                            Jenis Variasi
                        </label>

                        <select
                            id="variation-type"
                            class="form-select mb-3"
                        >
                            <option value="Tambah">
                                Tambah Nilai Kontrak
                            </option>

                            <option value="Tolak">
                                Kurangkan Nilai Kontrak
                            </option>
                        </select>

                        <label class="form-label">
                            Jumlah (RM)
                        </label>

                        <input
                            id="variation-amount"
                            type="number"
                            class="form-control mb-3"
                            min="0"
                            step="0.01"
                        >

                        <label class="form-label">
                            Tarikh
                        </label>

                        <input
                            id="variation-date"
                            type="date"
                            class="form-control"
                        >

                    </div>
                `,

                showCancelButton: true,
                confirmButtonText: 'Tambah Variasi',
                cancelButtonText: 'Batal',

                preConfirm: function () {

                    const description =
                        document.getElementById(
                            'variation-description'
                        ).value.trim();

                    const type =
                        document.getElementById(
                            'variation-type'
                        ).value;

                    const amount =
                        document.getElementById(
                            'variation-amount'
                        ).value;

                    const date =
                        document.getElementById(
                            'variation-date'
                        ).value;

                    if (!description) {
                        Swal.showValidationMessage(
                            'Masukkan penerangan variasi.'
                        );

                        return false;
                    }

                    if (!amount || Number(amount) <= 0) {
                        Swal.showValidationMessage(
                            'Masukkan jumlah yang sah.'
                        );

                        return false;
                    }

                    if (!date) {
                        Swal.showValidationMessage(
                            'Pilih tarikh variasi.'
                        );

                        return false;
                    }

                    return {
                        description: description,
                        type: type,
                        amount: amount,
                        date: date
                    };
                }

            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form =
                    document.createElement('form');

                form.method = 'POST';
                addCsrfToken(form);

                form.action =
                    window.APP_ROOT + 'project/addVariationRecord/' +
                    projectId;

                const values = {
                    description:
                        result.value.description,

                    variation_type:
                        result.value.type,

                    amount:
                        result.value.amount,

                    variation_date:
                        result.value.date
                };

                Object.keys(values).forEach(function (name) {

                    const input =
                        document.createElement('input');

                    input.type = 'hidden';
                    input.name = name;
                    input.value = values[name];

                    form.appendChild(input);

                });

                document.body.appendChild(form);

                form.submit();

            });

        });

    });

    document
    .querySelectorAll('.delete-schedule-btn')
    .forEach(function (button) {

        button.addEventListener('click', function () {

            const scheduleId =
                this.dataset.scheduleId;

            Swal.fire({
                title: 'Padam Jadual Tuntutan?',
                text: 'Jadual tuntutan ini akan dipadam secara kekal.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Padam',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545'
            }).then(function (result) {

                if (!result.isConfirmed) {
                    return;
                }

                const form =
                    document.createElement('form');

                form.method = 'POST';
                addCsrfToken(form);

                form.action =
                    window.APP_ROOT + 'project/deleteScheduleRecord/' +
                    scheduleId;

                document.body.appendChild(form);

                form.submit();

            });

        });

    });
    const paymentMethod =
        document.getElementById('paymentMethod');

    const addScheduleRowButton =
        document.getElementById('addScheduleRowButton');

    if (
        paymentMethod &&
        addScheduleRowButton
    ) {

        function updateScheduleButton() {

            if (
                paymentMethod.value === 'One-off'
            ) {

                addScheduleRowButton.style.display =
                    'none';

            } else {

                addScheduleRowButton.style.display =
                    '';

            }

        }


        paymentMethod.addEventListener(
            'change',
            updateScheduleButton
        );


        updateScheduleButton();

    }


});
