<div class="d-flex gap-2 mb-3"><a class="btn btn-outline-secondary" href="<?= PUBLIC_ROOT ?>project<?= ReportingYear::suffix() ?>">Kembali ke Senarai</a><a class="btn btn-outline-primary" href="<?= PUBLIC_ROOT ?>project/finance/<?= $project['id'] ?><?= ReportingYear::suffix() ?>">Pengurusan Kewangan</a></div>
<div class="card shadow-sm">

    <div class="card-header bg-white">
        <h5 class="mb-0">
            Edit Projek
        </h5>
    </div>

    <div class="card-body">

        <form
            method="POST"
            action="<?= PUBLIC_ROOT ?>project/update/<?= $project['id'] ?><?= ReportingYear::suffix() ?>"
        >
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="year" value="<?= ReportingYear::selected() ?>">

            <h6 class="fw-bold">
                Maklumat Asas
            </h6>

            <hr>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        ID Projek
                    </label>

                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        type="text"
                        name="project_id"
                        class="form-control"
                        value="<?= htmlspecialchars($project['project_id']) ?>"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Jabatan
                    </label>

                    <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        name="department"
                        class="form-select"
                        required
                    >
                        <?php
                        $departments = Departments::options();
                        ?>

                        <?php foreach ($departments as $department): ?>
                            <option
                                value="<?= htmlspecialchars($department) ?>"
                                <?= $project['department'] === $department ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($department) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Nama Projek
                    </label>

                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        type="text"
                        name="project_name"
                        class="form-control"
                        value="<?= htmlspecialchars($project['project_name']) ?>"
                        required
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Penerangan Projek
                    </label>

                    <textarea <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        name="description"
                        class="form-control"
                        rows="3"
                    ><?= htmlspecialchars((string) $project['description']) ?></textarea>
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

                        <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                            name="approval_status"
                            class="form-select"
                        >
                            <?php
                            $approvalStatuses = [
                                'Tangguh',
                                'Diluluskan',
                                'Ditolak'
                            ];
                            ?>

                            <?php foreach ($approvalStatuses as $status): ?>
                                <option
                                    value="<?= $status ?>"
                                    <?= $project['approval_status'] === $status ? 'selected' : '' ?>
                                >
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Bilangan Mesyuarat
                        </label>

                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                            type="number"
                            name="meeting_count"
                            class="form-control"
                            min="0"
                            value="<?= (int) $project['meeting_count'] ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Jenis Perolehan
                        </label>

                        <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                            name="procurement_type"
                            class="form-select"
                        >
                            <?php
                            $procurementTypes = [
                                'Tender',
                                'Sebut Harga',
                                'Pembelian Terus',
                                'Rundingan Terus'
                            ];
                            ?>

                            <?php foreach ($procurementTypes as $type): ?>
                                <option
                                    value="<?= $type ?>"
                                    <?= $project['procurement_type'] === $type ? 'selected' : '' ?>
                                >
                                    <?= $type ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Nilai SST (RM)
                        </label>

                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                            type="number"
                            name="sst_value"
                            class="form-control"
                            step="0.01"
                            min="0"
                            value="<?= $project['sst_value'] ?>"
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            Nilai Kelulusan Mesyuarat (RM)
                        </label>

                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                            type="number"
                            name="meeting_approval_value"
                            class="form-control"
                            step="0.01"
                            min="0"
                            value="<?= $project['meeting_approval_value'] ?>"
                        >
                    </div>

                </div>

            </div>

            <div
                class="border rounded p-3 mt-4"
                style="background:#eff6ff;"
            >

                <h6 class="fw-bold text-primary">
                    Perancangan Jadual Pembayaran
                </h6>

                <hr>

                <label class="form-label">
                    Kaedah Pembayaran
                </label>

                <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                    name="payment_method"
                    id="editPaymentMethod"
                    class="form-select mb-3"
                >
                    <?php
                    $paymentMethods = [
                        'One-off',
                        'Ansuran',
                        'Pajakan/Sewa'
                    ];
                    ?>

                    <?php foreach ($paymentMethods as $method): ?>
                        <option
                            value="<?= $method ?>"
                            <?= $project['payment_method'] === $method ? 'selected' : '' ?>
                        >
                            <?= $method ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="d-flex justify-content-between mb-2">

                    <strong>
                        Jadual Tuntutan / Pajakan
                    </strong>

                    <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                        type="button"
                        class="btn btn-link btn-sm"
                        onclick="addEditScheduleRow()"
                    >
                        + Tambah Baris
                    </button><?php endif; ?>

                </div>

                <div id="editScheduleContainer">

                    <?php if (!empty($schedules)): ?>

                        <?php foreach ($schedules as $schedule): ?>

                            <div class="schedule-row border rounded bg-white p-3 mb-2">

                                <div class="row g-2">

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Tarikh Tuntutan
                                        </label>

                                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                            type="date"
                                            name="schedule_date[]"
                                            class="form-control"
                                            value="<?= $schedule['claim_date'] ?>"
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">
                                            Jumlah (RM)
                                        </label>

                                        <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                            type="number"
                                            name="schedule_amount[]"
                                            class="form-control"
                                            step="0.01"
                                            min="0"
                                            value="<?= $schedule['amount'] ?>"
                                        >
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">
                                            Jenis
                                        </label>

                                        <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                            name="schedule_type[]"
                                            class="form-select edit-schedule-type"
                                        >
                                            <?php foreach ($paymentMethods as $method): ?>
                                                <option
                                                    value="<?= $method ?>"
                                                    <?= $schedule['type'] === $method ? 'selected' : '' ?>
                                                >
                                                    <?= $method ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-1 d-flex align-items-end">
                                        <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                                            type="button"
                                            class="btn btn-danger"
                                            onclick="this.closest('.schedule-row').remove()"
                                        >
                                            ×
                                        </button><?php endif; ?>
                                    </div>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="schedule-row border rounded bg-white p-3 mb-2">

                            <div class="row g-2">

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Tarikh Tuntutan
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="date"
                                        name="schedule_date[]"
                                        class="form-control"
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Jumlah (RM)
                                    </label>

                                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        type="number"
                                        name="schedule_amount[]"
                                        class="form-control"
                                        step="0.01"
                                        min="0"
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Jenis
                                    </label>

                                    <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                                        name="schedule_type[]"
                                        class="form-select edit-schedule-type"
                                    >
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <option
                                                value="<?= $method ?>"
                                                <?= $project['payment_method'] === $method ? 'selected' : '' ?>
                                            >
                                                <?= $method ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                            </div>

                        </div>

                    <?php endif; ?>

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

                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="<?= $project['start_date'] ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        Tarikh Tamat
                    </label>

                    <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="<?= $project['end_date'] ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">
                        Status Semasa Projek
                    </label>

                    <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                        name="project_status"
                        class="form-select"
                    >
                        <?php
                        $projectStatuses = [
                            'Belum Mula',
                            'Dalam Proses',
                            'Selesai',
                            'Dibatalkan'
                        ];
                        ?>

                        <?php foreach ($projectStatuses as $status): ?>
                            <option
                                value="<?= $status ?>"
                                <?= $project['project_status'] === $status ? 'selected' : '' ?>
                            >
                                <?= $status ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">

                <a
                    href="<?= PUBLIC_ROOT ?>project<?= ReportingYear::suffix() ?>"
                    class="btn btn-secondary"
                >
                    Batal
                </a>

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

<script>
function addEditScheduleRow() {

    const container =
        document.getElementById('editScheduleContainer');

    const paymentMethod =
        document.getElementById('editPaymentMethod').value;

    const row =
        document.createElement('div');

    row.className =
        'schedule-row border rounded bg-white p-3 mb-2';

    row.innerHTML = `
        <div class="row g-2">

            <div class="col-md-4">
                <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                    type="date"
                    name="schedule_date[]"
                    class="form-control"
                >
            </div>

            <div class="col-md-4">
                <input <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                    type="number"
                    name="schedule_amount[]"
                    class="form-control"
                    step="0.01"
                    min="0"
                >
            </div>

            <div class="col-md-3">
                <select <?= $_SESSION['user_role'] !== 'admin' ? 'disabled' : '' ?>
                    name="schedule_type[]"
                    class="form-select edit-schedule-type"
                >
                    <option value="${paymentMethod}">
                        ${paymentMethod}
                    </option>
                </select>
            </div>

            <div class="col-md-1">
                <?php if ($_SESSION['user_role'] === 'admin'): ?><button
                    type="button"
                    class="btn btn-danger"
                    onclick="this.closest('.schedule-row').remove()"
                >
                    ×
                </button><?php endif; ?>
            </div>

        </div>
    `;

    container.appendChild(row);
}

document
    .getElementById('editPaymentMethod')
    .addEventListener('change', function () {

        document
            .querySelectorAll('.edit-schedule-type')
            .forEach(function(select) {

                select.innerHTML =
                    '<option value="' +
                    this.value +
                    '">' +
                    this.value +
                    '</option>';

            }, this);

    });
</script>
