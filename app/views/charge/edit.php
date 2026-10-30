<?php require __DIR__ . '/validation.php'; ?>
<p class="text-muted">Tahun Rekod: <strong><?= isset($charge['record_year']) ? (int) $charge['record_year'] : 'Belum disahkan (rekod lama)' ?></strong></p>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>charge<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
        <div
            class="module-page"
        >

            <div class="module-container">

                <div class="card">

                    <form
                        method="POST"
                        action="<?= PUBLIC_ROOT ?>charge/update/<?= $charge['id'] ?><?= ReportingYear::suffix() ?>"
                        class="edit-charge-form"
                        data-old-input="<?= htmlspecialchars(json_encode(isset($oldInput) ? $oldInput : [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                        <div class="card-header">

                            <div>

                                <h5 class="card-title fw-bold">
                                    Edit Caj Pemajuan
                                </h5>

                                <div class="small text-muted">
                                    <?= htmlspecialchars((string) $charge['osc_id']) ?>
                                </div>

                            </div>



                        </div>


                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Nama Pemaju
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="text"
                                        name="developer_name"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['developer_name']) ?>"
                                        required
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Nama Projek
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="text"
                                        name="project_name"
                                        class="form-control"
                                        value="<?= htmlspecialchars($charge['project_name']) ?>"
                                        required
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        ID OSC
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="text"
                                        name="osc_id" required
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) $charge['osc_id']) ?>"
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        No. Lot / PT
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="text"
                                        name="lot_number" required
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) $charge['lot_number']) ?>"
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Mukim
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="text"
                                        name="mukim" required
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) $charge['mukim']) ?>"
                                    >

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        No. Fail
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="text"
                                        name="file_number" required
                                        class="form-control"
                                        value="<?= htmlspecialchars((string) $charge['file_number']) ?>"
                                    >

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Jumlah Caj (RM)
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="number"
                                        name="charge_amount"
                                        <?= $hasPaidPayments ? 'readonly' : '' ?>
                                        class="form-control"
                                        min="0.01"
                                        step="0.01"
                                        value="<?= htmlspecialchars($charge['charge_amount']) ?>"
                                        required
                                    >

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Keputusan OSC
                                    </label>

                                    <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        name="osc_decision" required
                                        class="form-select"
                                    >

                                        <option
                                            value="Lulus"
                                            <?= $charge['osc_decision'] === 'Lulus' ? 'selected' : '' ?>
                                        >
                                            Lulus
                                        </option>

                                        <option
                                            value="Tangguh"
                                            <?= $charge['osc_decision'] === 'Tangguh' ? 'selected' : '' ?>
                                        >
                                            Tangguh
                                        </option>

                                        <option
                                            value="Ditolak"
                                            <?= $charge['osc_decision'] === 'Ditolak' ? 'selected' : '' ?>
                                        >
                                            Ditolak
                                        </option>

                                    </select>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Pelan Bayaran
                                    </label>

                                    <?php if ($hasPaidPayments): ?>

                                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                            type="hidden"
                                            name="payment_plan"
                                            value="<?= htmlspecialchars($charge['payment_plan']) ?>"
                                        >

                                        <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                            class="form-select"
                                            disabled
                                        >

                                            <option selected>
                                                <?= htmlspecialchars($charge['payment_plan']) ?>
                                            </option>

                                        </select>

                                        <div class="form-text">
                                            Pelan bayaran dikunci kerana jadual bayaran telah diwujudkan.
                                        </div>

                                    <?php else: ?>

                                        <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                            name="payment_plan"
                                            class="form-select edit-payment-plan"
                                            required
                                        >

                                            <option
                                                value="Bayaran Penuh"
                                                <?= $charge['payment_plan'] === 'Bayaran Penuh' ? 'selected' : '' ?>
                                            >
                                                Bayaran Penuh
                                            </option>

                                            <option
                                                value="3 Ansuran"
                                                <?= $charge['payment_plan'] === '3 Ansuran' ? 'selected' : '' ?>
                                            >
                                                3 Ansuran
                                            </option>

                                            <option
                                                value="4 Ansuran"
                                                <?= $charge['payment_plan'] === '4 Ansuran' ? 'selected' : '' ?>
                                            >
                                                4 Ansuran
                                            </option>

                                        </select>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <?php if ($hasPayments): ?>

                                <hr class="my-4">


                                <div class="d-flex justify-content-between align-items-center mb-3">

                                    <div>

                                        <h6 class="fw-bold mb-1">
                                            Jadual Ansuran
                                        </h6>

                                        <div class="small text-muted">
                                            Ansuran yang telah dibayar tidak boleh diubah. Jumlah prinsipal semua ansuran mesti sepadan dengan Jumlah Caj apabila membuat perubahan kewangan. Jika Jumlah Caj dibetulkan, selaraskan ansuran belum dibayar sebelum menyimpan. Amaun dan tarikh tidak dikira semula secara automatik; bunga anggaran 8% tidak termasuk dalam prinsipal.
                                        </div>

                                    </div>

                                </div>


                                <div class="d-flex flex-column gap-3">

                                <div class="edit-installment-container">

                                        <?php foreach ($payments as $payment): ?>

                                            <div class="border rounded p-3">

                                                <div class="d-flex justify-content-between align-items-center mb-3">

                                                    <div class="fw-bold">
                                                        Ansuran #<?= (int) $payment['installment_no'] ?>
                                                    </div>


                                                    <?php if ($payment['status'] === 'Dibayar'): ?>

                                                        <span class="status-badge status-approved">
                                                            Telah Dibayar
                                                        </span>

                                                    <?php else: ?>

                                                        <span class="status-badge status-pending">
                                                            Menunggu
                                                        </span>

                                                    <?php endif; ?>

                                                </div>


                                                <?php if ($payment['status'] === 'Dibayar'): ?>

                                                    <div class="row g-3">

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Jumlah (RM)
                                                            </label>

                                                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                                                type="text"
                                                                class="form-control"
                                                                value="<?= number_format($payment['amount'], 2, '.', '') ?>"
                                                                disabled
                                                            >

                                                        </div>


                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tarikh Akhir
                                                            </label>

                                                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                                                type="date"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($payment['due_date'] ?: '') ?>"
                                                                disabled
                                                            >

                                                        </div>


                                                        <div class="col-12">

                                                            <div class="small text-muted">
                                                                Dibayar pada:
                                                                <strong>
                                                                    <?= $payment['payment_date']
                                                                        ? date(
                                                                            'd/m/Y',
                                                                            strtotime($payment['payment_date'])
                                                                        )
                                                                        : '-'
                                                                    ?>
                                                                </strong>
                                                            </div>

                                                        </div>

                                                    </div>


                                                <?php else: ?>

                                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                                        type="hidden"
                                                        name="payment_schedule_id[]"
                                                        value="<?= (int) $payment['id'] ?>"
                                                    >


                                                    <div class="row g-3">

                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Jumlah (RM)
                                                            </label>

                                                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                                                type="number"
                                                                name="payment_schedule_amount[]"
                                                                class="form-control"
                                                                min="<?= substr((string) $payment['amount'], 0, 1) === '-' ? htmlspecialchars((string) $payment['amount']) : ($charge['payment_plan'] === '3 Ansuran' || $payment['amount'] === '0.00' ? '0' : '0.01') ?>"
                                                                step="0.01"
                                                                value="<?= htmlspecialchars($payment['amount']) ?>"
                                                                required
                                                            >

                                                        </div>


                                                        <div class="col-md-6">

                                                            <label class="form-label">
                                                                Tarikh Akhir
                                                            </label>

                                                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                                                type="date"
                                                                name="payment_schedule_due_date[]"
                                                                class="form-control"
                                                                value="<?= htmlspecialchars($payment['due_date'] ?: '') ?>"
                                                                <?= ChargeValidation::date($payment['due_date']) ? 'required' : '' ?>
                                                            >

                                                        </div>

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                </div>

                            <?php else: ?>
                                <div class="edit-installment-container"></div>
                            <?php endif; ?>

                        </div>


                        <div class="card-footer">



                            <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Simpan Perubahan
                            </button><?php endif; ?>

                        </div>

                    </form>

                </div>

            </div>

        </div>
