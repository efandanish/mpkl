<?php

class ProjectController extends ModuleController
{

    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('Project');
        $this->loadModel('Notification');
        $this->requireLogin();
        $this->requirePostCsrf();
        $this->requireYearContext();
        $action = strtolower($this->request->param('action'));
        // Authenticated users may create; every other mutation remains admin-only.
        $isCreate = $action === 'create' && in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true);
        if (!$isCreate && ($_SERVER['REQUEST_METHOD'] !== 'GET' || !in_array($action, ['index', 'edit', 'finance'], true))) {
            $this->requireAdmin();
        }

    }


    public function index()
    {
        $year = ReportingYear::selected();
        $this->renderModule('project/index.php', ['projects' => $this->project->getAllByYear($year), 'undatedProjects' => $this->project->getUndated(), 'selectedYear' => $year, 'yearModule' => 'project']);
    }

    public function finance($id)
    {
        $project = $this->project->getById($id);
        if (!$project) {
            header('Location: ' . ReportingYear::url('project?error=project_not_found'));
            exit;
        }
        $schedules = $this->project->getSchedules($id);
        $sstAsal = (float) $project['sst_value'];
        // Preserve the existing modal's contract calculation.
        $kontrakTerkini = (float) $project['meeting_approval_value'];
        if ($kontrakTerkini <= 0) {
            $kontrakTerkini = $sstAsal;
        }
        $belanjaSebenar = (float) $this->project->getTotalExpenses($id);
        $this->renderModule('project/finance.php', [
            'project' => $project, 'schedules' => $schedules,
            'expenses' => $this->project->getExpenses($id),
            'variations' => $this->project->getVariations($id),
            'sstAsal' => $sstAsal, 'kontrakTerkini' => $kontrakTerkini,
            'belanjaSebenar' => $belanjaSebenar,
            'bakiKontrak' => $kontrakTerkini - $belanjaSebenar,
            'jumlahTerjadual' => array_sum(array_column($schedules, 'amount'))
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->renderModule('project/create.php');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        if (!$this->validateProjectInput(true)) { return; }
        $data = $this->getFormData();
        if ($this->project->projectIdExists($data['project_id'])) {
            $this->projectCreateErrors(['ID Projek telah digunakan. Sila pilih ID Projek yang lain; rekod sedia ada tidak diubah.']);
            return;
        }
        try {
            $projectId = $this->atomic(function () use ($data) {
                $projectId = $this->project->create($data);
                if (!$projectId) { throw new RuntimeException('Project insertion failed.'); }
                $this->saveSchedules($projectId);
                $this->notification->create([
                    'category' => 'project', 'action' => 'created', 'title' => 'Projek Baru',
                    'message' => mb_substr($data['project_id'] . ' - ' . $data['project_name'] . ' telah didaftarkan.', 0, 255),
                    'entity_type' => 'project', 'entity_id' => $projectId
                ]);
                return $projectId;
            });
        } catch (PDOException $error) {
            if (isset($error->errorInfo[1]) && (int) $error->errorInfo[1] === 1062) {
                $this->projectCreateErrors(['ID Projek telah digunakan. Sila pilih ID Projek yang lain.']);
            } else {
                error_log('Project create failed, SQLSTATE ' . $error->getCode());
                $this->projectCreateErrors(['Projek tidak dapat disimpan. Tiada perubahan disimpan. Sila cuba lagi atau hubungi pentadbir.'], 500);
            }
            return;
        } catch (RuntimeException $error) {
            $this->projectCreateErrors(['Projek tidak dapat disimpan. Sila cuba lagi atau hubungi pentadbir.'], 500);
            return;
        }
        // Direct record page guarantees visibility even when the optional start date is absent
        // or its year differs from the navigation year. No date/year is fabricated.
        header('Location: ' . ReportingYear::url('project/finance/' . $projectId . '?success=project_added'));
        exit;
    }


    public function edit($id)
    {
        $project = $this->project->getById($id);

        if (!$project) {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $schedules = $this->project->getSchedules($id);

        $this->renderModule('project/edit.php', ['project' => $project, 'schedules' => $schedules]);

    }


    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $oldProject =
            $this->project->getById($id);


        if (!$oldProject) {

            header(
                'Location: ' . ReportingYear::url('project?error=project_not_found')
            );

            exit;
        }

        if (!$this->validateProjectInput(false, $oldProject['department'])) { return; }
        $data = $this->getFormData();

        $changes = [];


        if (
            $oldProject['project_id'] !==
            $data['project_id']
        ) {
            $changes[] = 'ID Projek';
        }


        if (
            $oldProject['department'] !==
            $data['department']
        ) {
            $changes[] = 'Jabatan';
        }


        if (
            $oldProject['project_name'] !==
            $data['project_name']
        ) {
            $changes[] = 'Nama Projek';
        }


        if (
            $oldProject['description'] !==
            $data['description']
        ) {
            $changes[] = 'Penerangan Projek';
        }


        if (
            $oldProject['approval_status'] !==
            $data['approval_status']
        ) {
            $changes[] = 'Status Kelulusan';
        }


        if (
            (int) $oldProject['meeting_count'] !==
            (int) $data['meeting_count']
        ) {
            $changes[] = 'Bilangan Mesyuarat';
        }


        if (
            $oldProject['procurement_type'] !==
            $data['procurement_type']
        ) {
            $changes[] = 'Jenis Perolehan';
        }


        if (
            (float) $oldProject['sst_value'] !==
            (float) $data['sst_value']
        ) {
            $changes[] = 'Nilai SST';
        }


        if (
            (float) $oldProject['meeting_approval_value'] !==
            (float) $data['meeting_approval_value']
        ) {
            $changes[] = 'Nilai Kelulusan Mesyuarat';
        }


        if (
            $oldProject['payment_method'] !==
            $data['payment_method']
        ) {
            $changes[] = 'Kaedah Pembayaran';
        }


        if (
            $oldProject['start_date'] !==
            $data['start_date']
        ) {
            $changes[] = 'Tarikh Mula';
        }


        if (
            $oldProject['end_date'] !==
            $data['end_date']
        ) {
            $changes[] = 'Tarikh Tamat';
        }


        if (
            $oldProject['project_status'] !==
            $data['project_status']
        ) {
            $changes[] = 'Status Projek';
        }


        try {
            $this->atomic(function () use ($id, $data, $changes) {
                $this->project->lockById($id);
        $this->project->update(
            $id,
            $data
        );


        if ($this->projectSchedulesChanged($id)) {
            $existing = $this->project->getSchedules($id);
            $linkedExpenses = array_filter($this->project->getExpenses($id), function ($expense) { return !empty($expense['schedule_id']); });
            foreach ($existing as $schedule) {
                if ($schedule['status'] === 'Dibayar' || $linkedExpenses) {
                    throw new DomainException('Jadual dengan rekod bayaran tidak boleh diganti.');
                }
            }
            $this->project->deleteSchedules($id);
            $this->saveSchedules($id);
        }

        if (!empty($changes)) {

            $this->notification->create([
                'category' => 'project',

                'action' => 'updated',

                'title' =>
                    'Projek Dikemaskini',

                'message' =>
                    $data['project_id'] .
                    ' - ' .
                    $data['project_name'] .
                    ' dikemaskini: ' .
                    implode(', ', $changes) .
                    '.',

                'entity_type' =>
                    'project',

                'entity_id' =>
                    $id
            ]);
        }

            });
        } catch (DomainException $error) {
            $this->projectValidationError([$error->getMessage()]);
            return;
        }

        header(
            'Location: ' . ReportingYear::url('project/finance/' . $oldProject['id'] . '?success=project_updated')
        );

        exit;
    }


    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $project = $this->project->getById($id);

        if (!$project) {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $this->atomic(function () use ($id, $project) {
            $this->project->lockById($id);

            $this->notification->create([
            'category' => 'project',

            'action' => 'deleted',

            'title' => 'Projek Dipadam',

            'message' =>
                $project['project_id'] .
                ' - ' .
                $project['project_name'] .
                ' telah dipadam.',

            'entity_type' => 'project',

            'entity_id' => $id
            ]);
            $this->project->delete($id);

        });

        header('Location: ' . ReportingYear::url('project'));
        exit;
    }


    private function getFormData()
    {
        return [
            'project_id' => isset($_POST['project_id'])
                ? ChargeValidation::text($_POST['project_id'])
                : '',

            'department' => isset($_POST['department'])
                ? ChargeValidation::text($_POST['department'])
                : '',

            'project_name' => isset($_POST['project_name'])
                ? ChargeValidation::text($_POST['project_name'])
                : '',

            'description' => isset($_POST['description'])
                ? ChargeValidation::text($_POST['description'])
                : '',

            'approval_status' => isset($_POST['approval_status'])
                ? $_POST['approval_status']
                : 'Tangguh',

            'meeting_count' => isset($_POST['meeting_count'])
                ? (int) $_POST['meeting_count']
                : 0,

            'procurement_type' => isset($_POST['procurement_type'])
                ? $_POST['procurement_type']
                : '',

            'sst_value' => isset($_POST['sst_value'])
                ? (float) $_POST['sst_value']
                : 0,

            'meeting_approval_value' =>
                isset($_POST['meeting_approval_value'])
                ? (float) $_POST['meeting_approval_value']
                : 0,

            'payment_method' => isset($_POST['payment_method'])
                ? $_POST['payment_method']
                : 'One-off',

            'start_date' => isset($_POST['start_date'])
                ? $_POST['start_date']
                : '',

            'end_date' => isset($_POST['end_date'])
                ? $_POST['end_date']
                : '',

            'project_status' => isset($_POST['project_status'])
                ? $_POST['project_status']
                : 'Belum Mula'
        ];
    }


    private function projectCreateErrors(array $errors, $status = 422)
    {
        $this->response->setStatusCode($status);
        http_response_code($status);
        $this->renderModule('project/create.php', ['validationErrors' => $errors, 'oldInput' => $_POST]);
    }

    private function projectValidationError(array $errors)
    {
        $this->response->setStatusCode(422);
        http_response_code(422);
        header('Content-Type: text/html; charset=UTF-8');
        foreach ($errors as $message) { echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'; }
        echo '<p>Maklumat belum disimpan. Kembali ke borang untuk membetulkannya.</p>';
    }

    private function validateProjectInput($creating = false, $existingDepartment = null)
    {
        $errors = [];
        foreach (['project_id' => 50, 'department' => 150, 'project_name' => 255] as $key => $max) {
            $value = ChargeValidation::text(isset($_POST[$key]) ? $_POST[$key] : null);
            if ($value === '' || mb_strlen($value, 'UTF-8') > $max) { $errors[] = $key . ' wajib diisi dan dalam had panjang medan.'; }
        }
        $department = isset($_POST['department']) ? ChargeValidation::text($_POST['department']) : '';
        if (!Departments::valid($department) && ($creating || $department !== $existingDepartment)) {
            $errors[] = 'Jabatan tidak sah. Pilih Jabatan yang disenaraikan.';
        }
        foreach (['start_date', 'end_date'] as $key) {
            if (isset($_POST[$key]) && $_POST[$key] !== '' && !ChargeValidation::date($_POST[$key])) { $errors[] = $key . ' mesti tarikh kalendar sah.'; }
        }
        foreach (['sst_value', 'meeting_approval_value'] as $key) {
            $value = isset($_POST[$key]) ? $_POST[$key] : '';
            if ($value !== '' && $value !== '0' && $value !== '0.00' && !ChargeValidation::positiveAmount($value)) { $errors[] = $key . ' mesti amaun sah dan tidak negatif.'; }
        }
        foreach (['approval_status' => ['Diluluskan', 'Tangguh', 'Ditolak'], 'payment_method' => ['One-off', 'Ansuran', 'Pajakan/Sewa'], 'project_status' => ['Belum Mula', 'Dalam Proses', 'Selesai', 'Dibatalkan']] as $key => $values) {
            if (isset($_POST[$key]) && !in_array($_POST[$key], $values, true)) { $errors[] = $key . ' tidak sah.'; }
        }
        if (isset($_POST['meeting_count']) && (!is_string($_POST['meeting_count']) || !ctype_digit($_POST['meeting_count']) || strlen($_POST['meeting_count']) > 9)) { $errors[] = 'Bilangan mesyuarat tidak sah.'; }
        $keys = ['schedule_date', 'schedule_amount', 'schedule_type'];
        $hasRows = false;
        foreach ($keys as $key) { if (array_key_exists($key, $_POST)) { $hasRows = true; } }
        if ($hasRows) {
            $dates = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
            $amounts = isset($_POST['schedule_amount']) ? $_POST['schedule_amount'] : null;
            $types = isset($_POST['schedule_type']) ? $_POST['schedule_type'] : null;
            if (!is_array($dates) || !is_array($amounts) || !is_array($types) || array_keys($dates) !== array_keys($amounts) || array_keys($dates) !== array_keys($types)) {
                $errors[] = 'Struktur jadual projek tidak sah.';
            } else {
                foreach ($dates as $index => $date) {
                    if ($date === '' && $amounts[$index] === '') { continue; } // optional empty row
                    if (!ChargeValidation::date($date) || !ChargeValidation::positiveAmount($amounts[$index]) || !in_array($types[$index], ['One-off', 'Ansuran', 'Pajakan/Sewa'], true)) { $errors[] = 'Setiap jadual memerlukan tarikh, amaun positif dan jenis sah.'; }
                }
            }
        }
        if ($errors) {
            if ($creating) { $this->projectCreateErrors($errors); } else { $this->projectValidationError($errors); }
            return false;
        }
        return true;
    }

    private function projectSchedulesChanged($id)
    {
        if (!isset($_POST['schedule_date'])) { return false; }
        $submitted = [];
        foreach ($_POST['schedule_date'] as $index => $date) {
            if ($date === '' && $_POST['schedule_amount'][$index] === '') { continue; }
            $submitted[] = [$date, number_format((float) $_POST['schedule_amount'][$index], 2, '.', ''), $_POST['schedule_type'][$index]];
        }
        $existing = [];
        foreach ($this->project->getSchedules($id) as $row) { $existing[] = [$row['claim_date'], $row['amount'], $row['type']]; }
        return $submitted !== $existing;
    }

    private function saveSchedules($projectId)
    {
        if (
            empty($_POST['schedule_date']) ||
            empty($_POST['schedule_amount'])
        ) {
            return;
        }

        foreach ($_POST['schedule_date'] as $index => $date) {

            $amount = isset($_POST['schedule_amount'][$index])
                ? $_POST['schedule_amount'][$index]
                : 0;

            if (isset($_POST['schedule_type'][$index])) {
                $type = $_POST['schedule_type'][$index];
            } elseif (isset($_POST['payment_method'])) {
                $type = $_POST['payment_method'];
            } else {
                $type = 'One-off';
            }

            if (
                empty($date) ||
                (float) $amount <= 0
            ) {
                continue;
            }

            $this->project->addSchedule(
                $projectId,
                $date,
                $amount,
                $type
            );
        }
    }
    public function recordPayment($scheduleId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $schedule =
            $this->project->getScheduleById($scheduleId);

        if (!$schedule) {
            header(
                'Location: ' . ReportingYear::url('project?error=schedule_not_found')
            );
            exit;
        }

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $paymentDate = isset($_POST['payment_date'])
            ? $_POST['payment_date']
            : '';

        $description = isset($_POST['description'])
            ? ChargeValidation::text($_POST['description'])
            : '';

        if (!ChargeValidation::positiveAmount(isset($_POST['amount']) ? $_POST['amount'] : null) || !ChargeValidation::date($paymentDate)) {

            header(
                'Location: ' . ReportingYear::url('project?error=invalid_payment')
            );

            exit;
        }

        try {
        $this->atomic(function () use ($schedule, $scheduleId, $amount, $paymentDate, $description) {
            $this->project->lockById($schedule['project_id']);
            $current = $this->project->getScheduleById($scheduleId);
            if (!$current || $current['status'] !== 'Menunggu') { throw new DomainException('Jadual telah dibayar atau dipadam.'); }
            $this->project->addExpense(
            $schedule['project_id'],
            $scheduleId,
            $amount,
            $paymentDate,
            $description
            );

            $this->project->markSchedulePaid(
            $scheduleId
            );

        });

        } catch (DomainException $error) {
            $this->projectValidationError([$error->getMessage()]);
            return;
        }

        header(
            'Location: ' . ReportingYear::url('project/finance/' . $schedule['project_id'] . '?success=payment_recorded')
        );

        exit;
    }
    public function cancelPayment($expenseId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }
        $expense = $this->project->getExpenseById($expenseId);

        if (!$expense) {
            header('Location: ' . ReportingYear::url('project?error=expense_not_found'));
            exit;
        }

        $scheduleId = $expense['schedule_id'];

        $this->atomic(function () use ($expenseId, $scheduleId, $expense) {
            $this->project->lockById($expense['project_id']);
            $this->project->deleteExpense($expenseId);

            if (!empty($scheduleId) && !$this->project->scheduleHasExpenses($scheduleId)) {
            $this->project->markSchedulePending($scheduleId);
            }

        });

        header('Location: ' . ReportingYear::url('project/finance/' . $expense['project_id'] . '?success=payment_cancelled'));
        exit;
    }

    public function addScheduleRecord($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $project = $this->project->getById($projectId);

        if (!$project) {
            header('Location: ' . ReportingYear::url('project?error=project_not_found'));
            exit;
        }

        $date = isset($_POST['claim_date'])
            ? $_POST['claim_date']
            : '';

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $type = isset($_POST['type'])
            ? $_POST['type']
            : 'Ansuran';

        if (!ChargeValidation::date($date) || !ChargeValidation::positiveAmount(isset($_POST['amount']) ? $_POST['amount'] : null) || !in_array($type, ['One-off', 'Ansuran', 'Pajakan/Sewa'], true)) {
            header('Location: ' . ReportingYear::url('project?error=invalid_schedule'));
            exit;
        }

        $this->project->addSchedule(
            $projectId,
            $date,
            $amount,
            $type
        );

        header('Location: ' . ReportingYear::url('project/finance/' . $project['id'] . '?success=schedule_added'));
        exit;
    }
    public function addManualExpense($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $project = $this->project->getById($projectId);

        if (!$project) {
            header('Location: ' . ReportingYear::url('project?error=project_not_found'));
            exit;
        }

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $paymentDate = isset($_POST['payment_date'])
            ? $_POST['payment_date']
            : '';

        $description = isset($_POST['description'])
            ? ChargeValidation::text($_POST['description'])
            : '';

        if (!ChargeValidation::positiveAmount(isset($_POST['amount']) ? $_POST['amount'] : null) || !ChargeValidation::date($paymentDate)) {
            header('Location: ' . ReportingYear::url('project?error=invalid_expense'));
            exit;
        }

        $this->project->addExpense(
            $projectId,
            null,
            $amount,
            $paymentDate,
            $description
        );

        header('Location: ' . ReportingYear::url('project/finance/' . $project['id'] . '?success=manual_expense_added'));
        exit;
    }

    public function addVariationRecord($projectId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $project = $this->project->getById($projectId);

        if (!$project) {
            header('Location: ' . ReportingYear::url('project?error=project_not_found'));
            exit;
        }

        $description = isset($_POST['description'])
            ? ChargeValidation::text($_POST['description'])
            : '';

        $type = isset($_POST['variation_type'])
            ? $_POST['variation_type']
            : 'Tambah';

        $amount = isset($_POST['amount'])
            ? (float) $_POST['amount']
            : 0;

        $date = isset($_POST['variation_date'])
            ? $_POST['variation_date']
            : '';

        if (
            empty($description) ||
            !ChargeValidation::positiveAmount(isset($_POST['amount']) ? $_POST['amount'] : null) ||
            !ChargeValidation::date($date) || !in_array($type, ['Tambah', 'Tolak'], true)
        ) {
            header('Location: ' . ReportingYear::url('project?error=invalid_variation'));
            exit;
        }

        $this->project->addVariation(
            $projectId,
            $description,
            $type,
            $amount,
            $date
        );

        header('Location: ' . ReportingYear::url('project/finance/' . $project['id'] . '?success=variation_added'));
        exit;
    }

    public function deleteScheduleRecord($scheduleId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . ReportingYear::url('project'));
            exit;
        }

        $schedule =
            $this->project->getScheduleById($scheduleId);

        if (!$schedule) {
            header('Location: ' . ReportingYear::url('project?error=schedule_not_found'));
            exit;
        }

        if ($schedule['status'] === 'Dibayar') {
            header('Location: ' . ReportingYear::url('project?error=schedule_paid'));
            exit;
        }

        $deleted = $this->atomic(function () use ($schedule, $scheduleId) {
            $this->project->lockById($schedule['project_id']);
            $current = $this->project->getScheduleById($scheduleId);
            if (!$current || $current['status'] === 'Dibayar' || $this->project->scheduleHasExpenses($scheduleId)) { return false; }
            $this->project->deleteSchedule($scheduleId);
            return true;
        });
        if (!$deleted) {
            $this->projectValidationError(['Jadual dengan rekod bayaran tidak boleh dipadam.']);
            return;
        }

        header('Location: ' . ReportingYear::url('project/finance/' . $schedule['project_id'] . '?success=schedule_deleted'));
        exit;
    }
}
