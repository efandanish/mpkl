<?php
$oldInput = isset($oldInput) ? $oldInput : [];
$projectValue = function ($key, $default = '') use ($oldInput) {
    return isset($oldInput[$key]) && is_string($oldInput[$key]) ? htmlspecialchars($oldInput[$key], ENT_QUOTES, 'UTF-8') : $default;
};
?>
<?php if (!empty($validationErrors)): ?>
<div class="alert alert-danger" role="alert"><strong>Projek belum disimpan:</strong><ul>
<?php foreach ($validationErrors as $message): ?><li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
</ul></div>
<?php endif; ?>
<p class="text-muted">Projek ditapis mengikut tahun Tarikh Mula. Projek tanpa Tarikh Mula disenaraikan berasingan sebagai Belum Bertarikh.</p>
<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>project<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a></div>
<div class="module-page">
    <div class="module-container">

        <div class="card">

            <form method="POST" action="<?= PUBLIC_ROOT ?>project/create<?= ReportingYear::suffix() ?>" id="addProjectForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

                <div class="card-header">

                    <h5 class="card-title">
                        Tambah Projek Baru
                    </h5>



                </div>


                <div class="card-body">

                    <h6 class="fw-bold">
                        Maklumat Asas
                    </h6>

                    <hr>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                ID Projek
                            </label>

                            <input type="text" name="project_id" value="<?= $projectValue('project_id') ?>" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Jabatan
                            </label>

                            <select name="department" class="form-select" required>

                                <option value="" <?= isset($oldInput['department']) && $oldInput['department'] === '' ? 'selected' : '' ?>>
                                    Pilih Jabatan
                                </option>

                                <?php foreach (Departments::options() as $department): ?>
                                <option value="<?= htmlspecialchars($department) ?>" <?= isset($oldInput['department']) && $oldInput['department'] === $department ? 'selected' : '' ?>><?= htmlspecialchars($department) ?></option>
                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Nama Projek
                            </label>

                            <input type="text" name="project_name" value="<?= $projectValue('project_name') ?>" class="form-control" required>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Penerangan Projek
                            </label>

                            <textarea name="description" class="form-control" rows="3"><?= $projectValue('description') ?></textarea>

                        </div>

                    </div>


                    <div class="border rounded p-3 mt-4 bg-light">

                        <h6 class="fw-bold">
                            Kewangan & Kelulusan
                        </h6>

                        <hr>


                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label">
                                    Status Kelulusan
                                </label>

                                <select name="approval_status" class="form-select">

                                    <option value="Tangguh" <?= isset($oldInput['approval_status']) && $oldInput['approval_status'] === 'Tangguh' ? 'selected' : '' ?>>
                                        Tangguh
                                    </option>

                                    <option value="Diluluskan" <?= isset($oldInput['approval_status']) && $oldInput['approval_status'] === 'Diluluskan' ? 'selected' : '' ?>>
                                        Diluluskan
                                    </option>

                                    <option value="Ditolak" <?= isset($oldInput['approval_status']) && $oldInput['approval_status'] === 'Ditolak' ? 'selected' : '' ?>>
                                        Ditolak
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Bilangan Mesyuarat
                                </label>

                                <input type="number" name="meeting_count" class="form-control" min="0" value="<?= $projectValue('meeting_count', '0') ?>">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Jenis Perolehan
                                </label>

                                <select name="procurement_type" class="form-select">

                                    <option value="Tender" <?= isset($oldInput['procurement_type']) && $oldInput['procurement_type'] === 'Tender' ? 'selected' : '' ?>>
                                        Tender
                                    </option>

                                    <option value="Sebut Harga" <?= isset($oldInput['procurement_type']) && $oldInput['procurement_type'] === 'Sebut Harga' ? 'selected' : '' ?>>
                                        Sebut Harga
                                    </option>

                                    <option value="Pembelian Terus" <?= isset($oldInput['procurement_type']) && $oldInput['procurement_type'] === 'Pembelian Terus' ? 'selected' : '' ?>>
                                        Pembelian Terus
                                    </option>

                                    <option value="Rundingan Terus" <?= isset($oldInput['procurement_type']) && $oldInput['procurement_type'] === 'Rundingan Terus' ? 'selected' : '' ?>>
                                        Rundingan Terus
                                    </option>

                                </select>

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Nilai SST (RM)
                                </label>

                                <input type="number" name="sst_value" value="<?= $projectValue('sst_value') ?>" class="form-control" step="0.01" min="0">

                            </div>


                            <div class="col-md-6">

                                <label class="form-label">
                                    Nilai Kelulusan Mesyuarat (RM)
                                </label>

                                <input type="number" name="meeting_approval_value" value="<?= $projectValue('meeting_approval_value') ?>" class="form-control" step="0.01"
                                    min="0">

                            </div>

                        </div>

                    </div>


                    <div class="border rounded p-3 mt-4" style="background:#eff6ff;">

                        <h6 class="fw-bold text-primary">
                            Perancangan Jadual Pembayaran
                        </h6>

                        <hr>


                        <label class="form-label">
                            Kaedah Pembayaran
                        </label>

                        <select name="payment_method" id="paymentMethod" class="form-select mb-3">

                            <option value="One-off" <?= isset($oldInput['payment_method']) && $oldInput['payment_method'] === 'One-off' ? 'selected' : '' ?>>
                                One-off
                            </option>

                            <option value="Ansuran" <?= isset($oldInput['payment_method']) && $oldInput['payment_method'] === 'Ansuran' ? 'selected' : '' ?>>
                                Ansuran
                            </option>

                            <option value="Pajakan/Sewa" <?= isset($oldInput['payment_method']) && $oldInput['payment_method'] === 'Pajakan/Sewa' ? 'selected' : '' ?>>
                                Pajakan/Sewa
                            </option>

                        </select>


                        <div class="d-flex justify-content-between mb-2">

                            <strong>
                                Jadual Tuntutan / Pajakan
                            </strong>

                            <button type="button" class="btn btn-link btn-sm" onclick="addScheduleRow()"
                                id="addScheduleRowButton">
                                + Tambah Baris
                            </button>

                        </div>


                        <div id="scheduleContainer">
                            <?php require __DIR__ . '/create-schedule-rows.php'; ?>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4">
                        Garis Masa & Status Projek
                    </h6>

                    <hr>


                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Tarikh Mula
                            </label>

                            <input type="date" name="start_date" value="<?= $projectValue('start_date') ?>" class="form-control">

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Tarikh Tamat
                            </label>

                            <input type="date" name="end_date" value="<?= $projectValue('end_date') ?>" class="form-control">

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Status Semasa Projek
                            </label>

                            <select name="project_status" class="form-select">

                                <option value="Belum Mula" <?= isset($oldInput['project_status']) && $oldInput['project_status'] === 'Belum Mula' ? 'selected' : '' ?>>
                                    Belum Mula
                                </option>

                                <option value="Dalam Proses" <?= isset($oldInput['project_status']) && $oldInput['project_status'] === 'Dalam Proses' ? 'selected' : '' ?>>
                                    Dalam Proses
                                </option>

                                <option value="Selesai" <?= isset($oldInput['project_status']) && $oldInput['project_status'] === 'Selesai' ? 'selected' : '' ?>>
                                    Selesai
                                </option>

                                <option value="Dibatalkan" <?= isset($oldInput['project_status']) && $oldInput['project_status'] === 'Dibatalkan' ? 'selected' : '' ?>>
                                    Dibatalkan
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="card-footer">



                    <button type="submit" class="btn btn-success">
                        Tambah Projek
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
