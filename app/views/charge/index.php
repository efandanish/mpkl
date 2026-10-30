<a class="btn btn-primary mb-3" href="<?= PUBLIC_ROOT ?>charge/create<?= ReportingYear::suffix() ?>">+ Tambah Caj Pemajuan</a>
<?php if (array_filter($developmentCharges, function ($row) { return $row['record_year'] === null; })): ?>
<div class="alert alert-warning">Rekod lama dengan tahun belum disahkan dipaparkan sementara mengikut tahun pendaftaran asal. Tahun rekod perlu disemak berdasarkan dokumen sokongan.</div>
<?php endif; ?>
<div class="card dashboard-table-card mb-4">

    <div class="card-header dashboard-table-header">

        <div>
            <h5 class="mb-1 fw-bold">
                Senarai Caj Pemajuan
            </h5>

            <div class="text-muted small">
                Rekod caj pemajuan yang telah didaftarkan
            </div>
        </div>


        <div class="d-flex flex-wrap gap-2">

            <input type="text" id="chargeSearch" class="form-control form-control-sm table-search"
                placeholder="Cari ID OSC, Nama...">

            <select id="chargeStatusFilter" class="form-select form-select-sm table-filter">
                <option value="">
                    Semua Status
                </option>

                <option value="Belum Dibayar">
                    Belum Dibayar
                </option>

                <option value="Selesai Dibayar">
                    Selesai Dibayar
                </option>

                <option value="Bayaran Sebahagian">
                    Bayaran Sebahagian
                </option>
            </select>

            <button type="button" id="printChargeReport" class="btn btn-sm btn-dark">
                🖨 Cetak
            </button>

        </div>

    </div>


    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table dashboard-table align-middle">

                <thead>

                    <tr>
                        <th>Pemaju / Projek</th>
                        <th>Rujukan</th>
                        <th>Jumlah Caj</th>
                        <th>Jumlah Dibayar</th>
                        <th>Status Bayaran</th>
                        <th>Keputusan OSC</th>
                        <th class="text-end">
                            Tindakan
                        </th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (!empty($developmentCharges)): ?>

                        <?php foreach ($developmentCharges as $charge): ?>

                            <?php $totalPaid = $charge['total_paid']; $paymentStatus = $charge['payment_status']; ?>

                            <tr class="charge-row" data-search="<?=
                                strtolower(
                                    $charge['developer_name'] . ' ' .
                                    $charge['project_name'] . ' ' .
                                    $charge['osc_id']
                                )
                                ?>" data-payment-status="<?= htmlspecialchars($paymentStatus) ?>">

                                <td>

                                    <div class="table-primary-text">
                                        <?= htmlspecialchars($charge['developer_name']) ?>
                                    </div>

                                    <div class="table-secondary-text">
                                        <?= htmlspecialchars($charge['project_name']) ?>
                                        <?php if ($charge['record_year'] === null): ?><span class="badge bg-warning text-dark">Tahun belum disahkan</span><?php endif; ?>
                                    </div>

                                </td>


                                <td>

                                    <span class="reference-code">
                                        <?= htmlspecialchars((string) $charge['osc_id']) ?>
                                    </span>

                                </td>


                                <td>

                                    <div class="fw-semibold">
                                        RM <?= number_format($charge['charge_amount'], 2) ?>
                                    </div>

                                </td>


                                <td>

                                    <div class="fw-semibold">
                                        RM
                                        <?= number_format($totalPaid, 2) ?>
                                    </div>

                                </td>


                                <td>

                                    <?php if ($paymentStatus === 'Selesai Dibayar'): ?>

                                        <span class="status-badge status-approved">
                                            Selesai Dibayar
                                        </span>

                                    <?php elseif ($paymentStatus === 'Bayaran Sebahagian'): ?>

                                        <span class="status-badge status-review">
                                            Bayaran Sebahagian
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-pending">
                                            Belum Dibayar
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <?php if ($charge['osc_decision'] === 'Lulus'): ?>

                                        <span class="status-badge status-approved">
                                            Lulus
                                        </span>

                                    <?php elseif ($charge['osc_decision'] === 'Ditolak'): ?>

                                        <span class="status-badge status-rejected">
                                            Ditolak
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-review">
                                            <?= htmlspecialchars($charge['osc_decision']) ?>
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td class="text-end">

                                    <div class="d-flex justify-content-end gap-2">

                                        <a class="btn btn-sm btn-outline-secondary" href="<?= PUBLIC_ROOT ?>charge/payment/<?= $charge['id'] ?><?= ReportingYear::suffix() ?>">
                                            RM
                                        </a>

                                        <?php if ($_SESSION['user_role'] !== 'admin'): ?><a class="btn btn-sm btn-outline-primary" href="<?= PUBLIC_ROOT ?>charge/edit/<?= $charge['id'] ?><?= ReportingYear::suffix() ?>">Butiran</a><?php endif; ?>

                                        <?php if (
                                            isset($_SESSION['user_role']) &&
                                            $_SESSION['user_role'] === 'admin'
                                        ): ?>

                                            <a class="btn btn-sm btn-outline-primary" href="<?= PUBLIC_ROOT ?>charge/edit/<?= $charge['id'] ?><?= ReportingYear::suffix() ?>">
                                                Edit
                                            </a>


                                            <form method="POST" action="<?= PUBLIC_ROOT ?>charge/delete/<?= $charge['id'] ?><?= ReportingYear::suffix() ?>"
                                                class="delete-charge-form d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    Padam
                                                </button>

                                            </form>

                                        <?php endif; ?>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="7" class="text-center py-5">

                                <div class="empty-table-state">

                                    <div class="empty-table-icon">
                                        ▣
                                    </div>

                                    <div class="fw-semibold">
                                        Tiada Rekod Caj Pemajuan
                                    </div>

                                    <div class="small text-muted mt-1">
                                        Tiada rekod ditemui untuk tahun yang dipilih.
                                    </div>

                                </div>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>


        </div>

    </div>

</div>
