<?php require __DIR__ . '/validation.php'; ?>
<p class="text-muted">Tahun Rekod: <strong><?= isset($charge['record_year']) ? (int) $charge['record_year'] : 'Belum disahkan (rekod lama)' ?></strong></p>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>charge<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
        <div class="module-page">

            <div class="module-container">

                <div class="card">

                    <div class="card-header">

                        <div>

                            <h5 class="card-title fw-bold">
                                Rekod Bayaran
                            </h5>

                            <div class="text-muted small">
                                <?= htmlspecialchars($charge['developer_name']) ?>
                            </div>

                        </div>



                    </div>


                    <div class="card-body">

                        <h6 class="fw-bold mb-3">
                            Senarai Ansuran
                        </h6>


                        <?php if (!empty($payments)): ?>

                            <?php foreach ($payments as $payment): ?>

                                <div class="card mb-3">

                                    <div class="card-body">

                                        <div class="d-flex justify-content-between">

                                            <div>

                                                <div class="fw-bold">
                                                    Ansuran #<?= $payment['installment_no'] ?>
                                                </div>

                                                <div class="small text-muted">

                                                    Tarikh Akhir:

                                                    <?= $payment['due_date']
                                                        ? date(
                                                            'd/m/Y',
                                                            strtotime(
                                                                $payment['due_date']
                                                            )
                                                        )
                                                        : '-'
                                                        ?>

                                                </div>


                                                <div class="fw-semibold mt-2">

                                                    RM <?= number_format(
                                                        $payment['amount'],
                                                        2
                                                    ) ?>

                                                </div>

                                            </div>


                                            <div>

                                                <?php if (
                                                    $payment['status'] === 'Dibayar'
                                                ): ?>

                                                    <span class="status-badge status-approved">
                                                        Telah Dibayar
                                                    </span>

                                                <?php else: ?>

                                                    <span class="status-badge status-pending">
                                                        Menunggu
                                                    </span>

                                                <?php endif; ?>

                                            </div>

                                        </div>


                                        <?php if (
                                            $payment['status'] !== 'Dibayar'
                                        ): ?>

                                            <hr>


                                            <?php if ($_SESSION['user_role'] === 'admin'): ?><form method="POST" action="<?= PUBLIC_ROOT ?>charge/payment/<?= $payment['id'] ?><?= ReportingYear::suffix() ?>"
                                                enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                                                <?php
                                                $paymentOld = isset($paymentFormId) && (string) $paymentFormId === (string) $payment['id'] ? $oldPaymentInput : [];
                                                $paymentValue = function ($key) use ($paymentOld) {
                                                    return isset($paymentOld[$key]) && is_string($paymentOld[$key]) ? htmlspecialchars($paymentOld[$key], ENT_QUOTES, 'UTF-8') : '';
                                                };
                                                ?>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">No. Resit</label>
                                                        <input type="text" name="receipt_number" class="form-control" required maxlength="100" value="<?= $paymentValue('receipt_number') ?>">
                                                    </div>

                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Tarikh Bayaran / Tarikh Resit
                                                        </label>

                                                        <input type="date" name="payment_date" class="form-control" required value="<?= $paymentValue('payment_date') ?>">

                                                    </div>


                                                    <div class="col-md-6">

                                                        <label class="form-label">
                                                            Resit Bayaran
                                                        </label>

                                                        <input type="file" name="receipt" class="form-control"
                                                            accept=".jpg,.jpeg,.png,.pdf,image/*" required>

                                                        <div class="form-text">
                                                            JPG, PNG atau PDF. Maksimum 5 MB.
                                                        </div>

                                                    </div>


                                                    <div class="col-12">

                                                        <button type="submit" class="btn btn-primary">
                                                            Rekod Bayaran
                                                        </button>

                                                    </div>

                                                </div>

                                            </form><?php endif; ?>


                                        <?php else: ?>


                                            <div class="mt-3">

                                                <div class="small text-muted mb-3">

                                                    No. Resit:
                                                    <strong><?= isset($payment['receipt_number']) && $payment['receipt_number'] !== null && $payment['receipt_number'] !== ''
                                                        ? htmlspecialchars($payment['receipt_number'], ENT_QUOTES, 'UTF-8') : 'Belum direkodkan (rekod lama)' ?></strong>
                                                    <br>Tarikh Bayaran / Tarikh Resit:

                                                    <strong>

                                                        <?= $payment['payment_date']
                                                            ? date(
                                                                'd/m/Y',
                                                                strtotime(
                                                                    $payment['payment_date']
                                                                )
                                                            )
                                                            : '-'
                                                            ?>

                                                    </strong>

                                                </div>


                                                <div class="d-flex flex-wrap gap-2">

                                                    <?php if (
                                                        !empty(
                                                        $payment['receipt_file']
                                                    )
                                                    ): ?>

                                                        <a href="<?=
                                                            PUBLIC_ROOT .
                                                            htmlspecialchars(
                                                                $payment['receipt_file']
                                                            )
                                                            ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            Lihat Resit
                                                        </a>

                                                    <?php endif; ?>


                                                    <?php if ($_SESSION['user_role'] === 'admin'): ?><form method="POST" action="<?= PUBLIC_ROOT ?>charge/cancelPayment/<?= $payment['id'] ?><?= ReportingYear::suffix() ?>"
                                                        class="cancel-charge-payment-form">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            Batalkan Bayaran
                                                        </button>

                                                    </form><?php endif; ?>

                                                </div>

                                            </div>


                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>


                        <?php else: ?>


                            <div class="text-muted mb-3">
                                Tiada jadual bayaran direkodkan.
                            </div>


                            <?php if ($charge['payment_plan'] === '3 Ansuran'): ?>
                                <p class="small text-muted">Jadual 3 Ansuran bagi rekod sedia ada tidak dijana semula secara automatik. Jadual lama dikekalkan.</p>
                            <?php elseif ($_SESSION['user_role'] === 'admin'): ?><form method="POST" action="<?= PUBLIC_ROOT ?>charge/generatePaymentSchedule/<?= $charge['id'] ?><?= ReportingYear::suffix() ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                                <button type="submit" class="btn btn-outline-primary">
                                    Jana Jadual Bayaran
                                </button>

                            </form><?php endif; ?>


                        <?php endif; ?>

                    </div>


                    <div class="card-footer">



                    </div>

                </div>

            </div>

        </div>
