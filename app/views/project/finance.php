<?php if (isset($_GET['success']) && $_GET['success'] === 'project_added'): ?>
<div class="alert alert-success" role="alert">Projek berjaya didaftarkan.</div>
<?php endif; ?>
<a class="btn btn-outline-primary mb-3" href="<?= PUBLIC_ROOT ?>project/edit/<?= $project['id'] ?><?= ReportingYear::suffix() ?>">Edit / Butiran Projek</a>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>project<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
        <div class="module-page">

            <div class="module-container">

                <div class="card">


                    <!-- HEADER -->

                    <div class="card-header project-finance-header">

                        <div>

                            <div class="d-flex align-items-center gap-2 mb-1">

                                <h5 class="card-title fw-bold mb-0">
                                    Urus Kewangan Projek
                                </h5>

                                <span class="reference-code">
                                    <?= htmlspecialchars($project['project_id']) ?>
                                </span>

                            </div>

                            <div class="text-muted small">
                                <?= htmlspecialchars($project['department']) ?>
                            </div>

                        </div>




                    </div>


                    <form method="POST" action="<?= PUBLIC_ROOT ?>project/update/<?= $project['id'] ?><?= ReportingYear::suffix() ?>" class="edit-project-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                        <div class="card-body">


                            <!-- SUMMARY -->

                            <div class="row g-3 mb-4">

                                <div class="col-md-6 col-xl-3">

                                    <div class="finance-kpi">

                                        <div class="finance-kpi-label">
                                            SST Asal
                                        </div>

                                        <div class="finance-kpi-value">
                                            RM <?= number_format($sstAsal, 2) ?>
                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-6 col-xl-3">

                                    <div class="finance-kpi">

                                        <div class="finance-kpi-label">
                                            Kontrak Terkini
                                        </div>

                                        <div class="finance-kpi-value">
                                            RM <?= number_format($kontrakTerkini, 2) ?>
                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-6 col-xl-3">

                                    <div class="finance-kpi">

                                        <div class="finance-kpi-label">
                                            Belanja Sebenar
                                        </div>

                                        <div class="finance-kpi-value text-success">
                                            RM <?= number_format($belanjaSebenar, 2) ?>
                                        </div>

                                    </div>

                                </div>


                                <div class="col-md-6 col-xl-3">

                                    <div class="finance-kpi">

                                        <div class="finance-kpi-label">
                                            Baki Kontrak
                                        </div>

                                        <div class="finance-kpi-value">
                                            RM <?= number_format($bakiKontrak, 2) ?>
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- MAKLUMAT PROJEK -->

                            <div class="finance-section mb-4">

                                <div class="finance-section-header">

                                    <div>
                                        <h6 class="mb-1 fw-bold">
                                            Maklumat Projek
                                        </h6>

                                        <div class="small text-muted">
                                            Maklumat asas dan status projek
                                        </div>
                                    </div>

                                </div>


                                <div class="row g-3">

                                    <div class="col-md-6">

                                        <label class="form-label">
                                            Nama Projek
                                        </label>

                                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="text" name="project_name" class="form-control"
                                            value="<?= htmlspecialchars($project['project_name']) ?>" required>

                                    </div>


                                    <div class="col-md-3">

                                        <label class="form-label">
                                            Status Projek
                                        </label>

                                        <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> name="project_status" class="form-select">

                                            <?php
                                            $statuses = [
                                                'Belum Mula',
                                                'Dalam Proses',
                                                'Selesai',
                                                'Dibatalkan'
                                            ];
                                            ?>

                                            <?php foreach ($statuses as $status): ?>

                                                <option value="<?= $status ?>" <?= $project['project_status'] === $status ? 'selected' : '' ?>>
                                                    <?= $status ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="col-md-3">

                                        <label class="form-label">
                                            Siling Kontrak
                                        </label>

                                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="number" name="meeting_approval_value" class="form-control" step="0.01"
                                            min="0" value="<?= $project['meeting_approval_value'] ?>">

                                    </div>

                                </div>

                            </div>


                            <!-- MAIN FINANCIAL AREA -->

                            <div class="row g-4 mb-4">


                                <!-- JADUAL TUNTUTAN -->

                                <div class="col-xl-7">

                                    <div class="finance-section h-100">

                                        <div class="finance-section-header">

                                            <div>
                                                <h6 class="mb-1 fw-bold">
                                                    Jadual Tuntutan
                                                </h6>

                                                <div class="small text-muted">
                                                    Jadual bayaran dan tuntutan projek
                                                </div>
                                            </div>

                                            <?php if ($_SESSION['user_role'] === 'admin'): ?><button type="button" class="btn btn-sm btn-outline-primary add-schedule-btn"
                                                data-project-id="<?= $project['id'] ?>">
                                                + Tambah Baris
                                            </button><?php endif; ?>

                                        </div>


                                        <?php if (!empty($schedules)): ?>

                                            <div class="schedule-list">

                                                <?php foreach ($schedules as $schedule): ?>

                                                    <div class="schedule-item">

                                                        <div class="schedule-item-top">

                                                            <span class="status-badge status-pending">
                                                                <?= htmlspecialchars($schedule['status']) ?>
                                                            </span>

                                                            <div class="schedule-item-actions">

                                                                <?php if ($schedule['status'] !== 'Dibayar'): ?>

                                                                    <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                                                                        type="button"
                                                                        class="btn btn-sm btn-primary record-payment-btn"
                                                                        data-schedule-id="<?= $schedule['id'] ?>"
                                                                        data-amount="<?= $schedule['amount'] ?>"
                                                                        data-date="<?= $schedule['claim_date'] ?>"
                                                                    >
                                                                        Rekod Bayar
                                                                    </button><?php endif; ?>

                                                                    <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-danger delete-schedule-btn btn-delete-x"
                                                                        data-schedule-id="<?= $schedule['id'] ?>"
                                                                        title="Padam Jadual"
                                                                    >
                                                                        &times;
                                                                    </button><?php endif; ?>

                                                                <?php endif; ?>

                                                            </div>

                                                        </div>


                                                        <div class="row g-2 mt-1">

                                                            <div class="col-md-4">

                                                                <label class="small text-muted mb-1">
                                                                    Tarikh
                                                                </label>

                                                                <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="date" name="schedule_date[]" class="form-control"
                                                                    value="<?= htmlspecialchars($schedule['claim_date']) ?>">

                                                            </div>


                                                            <div class="col-md-4">

                                                                <label class="small text-muted mb-1">
                                                                    Jumlah
                                                                </label>

                                                                <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="number" name="schedule_amount[]" class="form-control"
                                                                    step="0.01" min="0"
                                                                    value="<?= htmlspecialchars($schedule['amount']) ?>">

                                                            </div>


                                                            <div class="col-md-4">

                                                                <label class="small text-muted mb-1">
                                                                    Jenis
                                                                </label>

                                                                <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> name="schedule_type[]" class="form-select">

                                                                    <option value="One-off" <?= $schedule['type'] === 'One-off' ? 'selected' : '' ?>>
                                                                        One-off
                                                                    </option>

                                                                    <option value="Ansuran" <?= $schedule['type'] === 'Ansuran' ? 'selected' : '' ?>>
                                                                        Ansuran
                                                                    </option>

                                                                    <option value="Pajakan/Sewa" <?= $schedule['type'] === 'Pajakan/Sewa' ? 'selected' : '' ?>>
                                                                        Pajakan/Sewa
                                                                    </option>

                                                                </select>

                                                            </div>

                                                        </div>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php else: ?>

                                            <div class="finance-empty-state">
                                                Tiada jadual tuntutan direkodkan.
                                            </div>

                                        <?php endif; ?>


                                        <div class="finance-total">

                                            <span>
                                                Jumlah Terjadual
                                            </span>

                                            <strong>
                                                RM <?= number_format($jumlahTerjadual, 2) ?>
                                            </strong>

                                        </div>

                                    </div>

                                </div>


                                <!-- REKOD BELANJA -->

                                <div class="col-xl-5">

                                    <div class="finance-section h-100">

                                        <div class="finance-section-header">

                                            <div>
                                                <h6 class="mb-1 fw-bold">
                                                    Rekod Belanja
                                                </h6>

                                                <div class="small text-muted">
                                                    Bayaran sebenar projek
                                                </div>
                                            </div>

                                            <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                                                type="button"
                                                class="btn btn-sm btn-outline-success manual-expense-btn"
                                                data-project-id="<?= $project['id'] ?>">
                                                + Rekod Manual
                                            </button><?php endif; ?>

                                        </div>


                                        <?php if (!empty($expenses)): ?>

                                            <div class="expense-list">

                                                <?php foreach ($expenses as $expense): ?>

                                                    <div class="expense-record position-relative">

                                                        <?php if ($_SESSION['user_role'] === 'admin'): ?><button type="button" class="expense-delete-btn"
                                                            data-expense-id="<?= $expense['id'] ?>" title="Batalkan Bayaran">
                                                            ×
                                                        </button><?php endif; ?>


                                                        <div class="mb-2">

                                                            <span class="status-badge status-approved">

                                                            <?php if (!empty($expense['schedule_type'])): ?>

                                                                <?= htmlspecialchars($expense['schedule_type']) ?>

                                                            <?php else: ?>

                                                                Manual

                                                            <?php endif; ?>

                                                        </span>

                                                        </div>


                                                        <div class="fw-bold fs-5 mb-1">
                                                            RM <?= number_format($expense['amount'], 2) ?>
                                                        </div>


                                                        <?php if (!empty($expense['claim_date'])): ?>

                                                            <div class="small text-muted">
                                                                Tuntutan:
                                                                <?= date(
                                                                    'd/m/Y',
                                                                    strtotime($expense['claim_date'])
                                                                ) ?>
                                                            </div>

                                                        <?php endif; ?>


                                                        <?php if (!empty($expense['description'])): ?>

                                                            <div class="small mt-2">
                                                                <?= htmlspecialchars((string) $expense['description']) ?>
                                                            </div>

                                                        <?php endif; ?>


                                                        <div class="small text-muted mt-2">
                                                            Dibayar:
                                                            <?= date(
                                                                'd/m/Y',
                                                                strtotime($expense['payment_date'])
                                                            ) ?>
                                                        </div>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php else: ?>

                                            <div class="finance-empty-state">
                                                Belum ada rekod belanja.
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>


                            <!-- VOT -->

                            <div class="finance-section">

                                <div class="finance-section-header">

                                    <div>
                                        <h6 class="mb-1 fw-bold">
                                            Variasi Kontrak / VOT
                                        </h6>

                                        <div class="small text-muted">
                                            Pelarasan nilai kontrak projek
                                        </div>
                                    </div>

                                    <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary add-variation-btn"
                                        data-project-id="<?= $project['id'] ?>">
                                        + Tambah Variasi
                                    </button><?php endif; ?>

                                </div>


                                <?php if (!empty($variations)): ?>

                                    <div class="variation-list">

                                        <?php foreach ($variations as $variation): ?>

                                            <div class="variation-item">

                                                <div class="d-flex justify-content-between align-items-start">

                                                    <div>

                                                        <div class="fw-semibold">
                                                            <?= htmlspecialchars($variation['description']) ?>
                                                        </div>

                                                        <div class="small text-muted mt-1">
                                                            <?= date(
                                                                'd/m/Y',
                                                                strtotime($variation['variation_date'])
                                                            ) ?>
                                                        </div>

                                                    </div>

                                                    <div
                                                        class="fw-bold <?= $variation['variation_type'] === 'Tambah' ? 'text-success' : 'text-danger' ?>"
                                                    >
                                                        <?= $variation['variation_type'] === 'Tambah' ? '+' : '-' ?>

                                                        RM <?= number_format($variation['amount'], 2) ?>
                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    </div>

                                <?php else: ?>

                                    <div class="finance-empty-state">
                                        Belum ada variasi kontrak direkodkan.
                                    </div>

                                <?php endif; ?>


                            <!-- HIDDEN FIELDS -->

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="project_id" value="<?= htmlspecialchars($project['project_id']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="department" value="<?= htmlspecialchars($project['department']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="description" value="<?= htmlspecialchars((string) $project['description']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="approval_status"
                                value="<?= htmlspecialchars($project['approval_status']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="meeting_count" value="<?= (int) $project['meeting_count'] ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="procurement_type"
                                value="<?= htmlspecialchars((string) $project['procurement_type']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="sst_value" value="<?= $project['sst_value'] ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="payment_method"
                                value="<?= htmlspecialchars($project['payment_method']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="start_date" value="<?= htmlspecialchars((string) $project['start_date']) ?>">

                            <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?> type="hidden" name="end_date" value="<?= htmlspecialchars((string) $project['end_date']) ?>">

                        </div>


                        <div class="card-footer">

                            <button
                                type="button"
                                class="btn btn-outline-secondary print-project-btn"
                            >
                                Cetak Laporan
                            </button>



                            <?php if ($_SESSION['user_role'] === 'admin'): ?><button type="submit" class="btn btn-primary">
                                Simpan Rekod
                            </button><?php endif; ?>

                        </div>

                    </form>

                </div>

            </div>

        </div>
