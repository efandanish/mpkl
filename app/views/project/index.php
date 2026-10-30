<a class="btn btn-primary mb-3" href="<?= PUBLIC_ROOT ?>project/create<?= ReportingYear::suffix() ?>">+ Tambah Projek</a>
<div class="card dashboard-table-card">

    <div class="card-header dashboard-table-header">

        <div>
            <h5 class="mb-1 fw-bold">
                Senarai Projek Pembangunan
            </h5>

            <div class="text-muted small">
                Senarai projek dan status kewangan semasa
            </div>
        </div>


        <div class="d-flex flex-wrap gap-2">

            <input type="text" id="projectSearch" class="form-control form-control-sm table-search"
                placeholder="Cari ID Projek...">


            <select id="departmentFilter" class="form-select form-select-sm table-filter">
                <option value="">
                    Semua Jabatan
                </option>

                <option value="Jabatan Kejuruteraan">
                    Jabatan Kejuruteraan
                </option>

                <option value="Jabatan Khidmat Pengurusan">
                    Jabatan Khidmat Pengurusan
                </option>

                <option value="Jabatan Landskap">
                    Jabatan Landskap
                </option>

                <option value="Jabatan Perancang Bandar">
                    Jabatan Perancang Bandar
                </option>
            </select>


            <select id="projectStatusFilter" class="form-select form-select-sm table-filter">
                <option value="">
                    Semua Status Projek
                </option>

                <option value="Belum Mula">
                    Belum Mula
                </option>

                <option value="Dalam Proses">
                    Dalam Proses
                </option>

                <option value="Selesai">
                    Selesai
                </option>

                <option value="Dibatalkan">
                    Dibatalkan
                </option>
            </select>


            <select id="projectPaymentFilter" class="form-select form-select-sm table-filter">
                <option value="">
                    Semua Status Bayaran
                </option>

                <option value="Belum Dibayar">
                    Belum Dibayar
                </option>

                <option value="Selesai Dibayar">
                    Selesai Dibayar
                </option>
            </select>

            <button type="button" id="printProjectReport" class="btn btn-sm btn-dark">
                🖨 Cetak
            </button>

        </div>

    </div>


    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table dashboard-table align-middle">

                <thead>

                    <tr>
                        <th>Projek / Jabatan</th>
                        <th>Kos</th>
                        <th>Jumlah Dibayar</th>
                        <th>Status Projek</th>
                        <th>Status Bayaran</th>
                        <th>Tempoh Projek</th>
                        <th class="text-end">Tindakan</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if (!empty($projects)): ?>

                        <?php foreach ($projects as $project): ?>

                            <tr class="project-row" data-search="<?=
                                strtolower(
                                    $project['project_id'] . ' ' .
                                    $project['project_name'] . ' ' .
                                    $project['department']
                                )
                                ?>" data-department="<?= htmlspecialchars($project['department']) ?>"
                                data-project-status="<?= htmlspecialchars($project['project_status']) ?>"
                                data-payment-status="Belum Dibayar">

                                <td>

                                    <div class="table-primary-text">
                                        <?= htmlspecialchars($project['project_name']) ?>
                                    </div>

                                    <div class="table-secondary-text">
                                        <?= htmlspecialchars($project['department']) ?>
                                    </div>

                                </td>


                                <td>

                                    <div class="fw-semibold">
                                        RM <?= number_format($project['meeting_approval_value'], 2) ?>
                                    </div>

                                </td>


                                <td>

                                    <div class="fw-semibold">
                                        RM 0.00
                                    </div>

                                </td>


                                <td>

                                    <?php if ($project['project_status'] === 'Selesai'): ?>

                                        <span class="status-badge status-approved">
                                            Selesai
                                        </span>

                                    <?php elseif ($project['project_status'] === 'Dalam Proses'): ?>

                                        <span class="status-badge status-review">
                                            Dalam Proses
                                        </span>

                                    <?php elseif ($project['project_status'] === 'Dibatalkan'): ?>

                                        <span class="status-badge status-rejected">
                                            Dibatalkan
                                        </span>

                                    <?php else: ?>

                                        <span class="status-badge status-pending">
                                            Belum Mula
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <td>

                                    <span class="status-badge status-pending">
                                        Belum Dibayar
                                    </span>

                                </td>


                                <td>

                                    <div class="small fw-semibold">
                                        <?= htmlspecialchars((string) $project['start_date']) ?>
                                    </div>

                                    <div class="small text-muted">
                                        hingga
                                        <?= htmlspecialchars((string) $project['end_date']) ?>
                                    </div>

                                </td>


                                <td class="text-end">

                                    <div class="d-flex justify-content-end gap-2">

                                        <a class="btn btn-sm btn-outline-primary" href="<?= PUBLIC_ROOT ?>project/finance/<?= $project['id'] ?><?= ReportingYear::suffix() ?>">
                                            Kewangan
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary" href="<?= PUBLIC_ROOT ?>project/edit/<?= $project['id'] ?><?= ReportingYear::suffix() ?>">Butiran</a>


                                        <?php if (
                                            isset($_SESSION['user_role']) &&
                                            $_SESSION['user_role'] === 'admin'
                                        ): ?>

                                            <form method="POST" action="<?= PUBLIC_ROOT ?>project/delete/<?= $project['id'] ?><?= ReportingYear::suffix() ?>"
                                                class="delete-project-form d-inline">
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
                                        ▤
                                    </div>

                                    <div class="fw-semibold">
                                        Tiada Projek Pembangunan
                                    </div>

                                    <div class="small text-muted mt-1">
                                        Tiada projek ditemui untuk tahun yang dipilih.
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

<?php require __DIR__ . "/undated.php"; ?>
