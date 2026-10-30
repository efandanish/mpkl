<?php
$oldInput = isset($oldInput) ? $oldInput : [];
$oldValue = function ($key) use ($oldInput) {
    return isset($oldInput[$key]) && is_scalar($oldInput[$key])
        ? htmlspecialchars((string) $oldInput[$key], ENT_QUOTES, 'UTF-8') : '';
};
?>
<?php require __DIR__ . '/validation.php'; ?>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>charge<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
<p class="text-muted">Tahun Rekod: <strong><?= ReportingYear::selected() ?></strong>. Caj ini akan didaftarkan bagi tahun tersebut.</p>
<div class="module-page">

    <div class="module-container">

        <div class="card">

            <form method="POST" action="<?= PUBLIC_ROOT ?>charge/create<?= ReportingYear::suffix() ?>" id="addChargeForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                <div class="card-header">

                    <div>

                        <h5 class="card-title fw-bold">
                            Daftar Caj Pemajuan
                        </h5>

                        <div class="small text-muted">
                            Masukkan maklumat kelulusan dan caj pemajuan
                        </div>

                    </div>



                </div>


                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Nama Pemaju
                            </label>

                            <input type="text" name="developer_name" value="<?= $oldValue('developer_name') ?>" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Nama Projek
                            </label>

                            <input type="text" name="project_name" value="<?= $oldValue('project_name') ?>" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                ID OSC
                            </label>

                            <input type="text" name="osc_id" required value="<?= $oldValue('osc_id') ?>" class="form-control" placeholder="Contoh: OSC-2026-0148">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                No. Lot / PT
                            </label>

                            <input type="text" name="lot_number" required value="<?= $oldValue('lot_number') ?>" class="form-control" placeholder="Contoh: PT 4587">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Mukim
                            </label>

                            <input type="text" name="mukim" required value="<?= $oldValue('mukim') ?>" class="form-control">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                No. Fail
                            </label>

                            <input type="text" name="file_number" required value="<?= $oldValue('file_number') ?>" class="form-control">

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Jumlah Caj (RM)
                            </label>

                            <input type="number" name="charge_amount" value="<?= $oldValue('charge_amount') ?>" class="form-control" min="0.01" step="0.01" required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Keputusan OSC
                            </label>

                            <select name="osc_decision" required class="form-select">

                                <option value="Lulus" <?= isset($oldInput['osc_decision']) && $oldInput['osc_decision'] === 'Lulus' ? 'selected' : '' ?>>
                                    Lulus
                                </option>

                                <option value="Tangguh" <?= isset($oldInput['osc_decision']) && $oldInput['osc_decision'] === 'Tangguh' ? 'selected' : '' ?>>
                                    Tangguh
                                </option>

                                <option value="Ditolak" <?= isset($oldInput['osc_decision']) && $oldInput['osc_decision'] === 'Ditolak' ? 'selected' : '' ?>>
                                    Ditolak
                                </option>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Pelan Bayaran
                            </label>

                            <select name="payment_plan" id="chargePaymentPlan" class="form-select" required>
                                <option value="Bayaran Penuh" <?= isset($oldInput['payment_plan']) && $oldInput['payment_plan'] === 'Bayaran Penuh' ? 'selected' : '' ?>>
                                    Bayaran Penuh (1x)
                                </option>

                                <option value="3 Ansuran" <?= isset($oldInput['payment_plan']) && $oldInput['payment_plan'] === '3 Ansuran' ? 'selected' : '' ?>>
                                    3 Ansuran
                                </option>

                                <option value="4 Ansuran" <?= isset($oldInput['payment_plan']) && $oldInput['payment_plan'] === '4 Ansuran' ? 'selected' : '' ?>>
                                    4 Ansuran
                                </option>
                            </select>

                        </div>


                        <div class="col-12" id="chargeInstallmentSection" style="display:none;">

                            <div class="border rounded p-3 bg-light">

                                <div class="fw-semibold mb-3">
                                    Butiran Ansuran
                                </div>

                                <div id="chargeAutomaticControls" <?= isset($oldInput['payment_plan']) && $oldInput['payment_plan'] === '3 Ansuran' ? '' : 'hidden' ?>>
                                    <label class="form-label" for="chargeFirstDeadline">Tarikh Akhir Ansuran 1</label>
                                    <input type="date" id="chargeFirstDeadline" name="first_installment_due_date" class="form-control mb-2" value="<?= $oldValue('first_installment_due_date') ?>" <?= isset($oldInput['payment_plan']) && $oldInput['payment_plan'] === '3 Ansuran' ? 'required' : 'disabled' ?>>
                                    <p class="small text-muted">Ansuran 2 dan 3 dijana setiap 3 bulan. Tarikh hujung bulan diselaraskan kepada hari terakhir bulan jika perlu.</p>
                                    <p>Anggaran Bunga 8%: <strong id="chargeInterestEstimate">—</strong></p>
                                    <p class="small text-muted">Anggaran rujukan sahaja; tidak termasuk dalam prinsipal, jumlah perlu dibayar atau kutipan.</p>
                                    <p id="chargeInstallmentError" class="text-danger" role="alert"></p>
                                </div>
                                <div id="chargeInstallmentRows" data-old-input="<?= htmlspecialchars(json_encode($oldInput, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"></div>

                            </div>

                        </div>

                    </div>

                </div>


                <div class="card-footer">



                    <button type="submit" class="btn btn-primary">
                        Daftar Caj
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
