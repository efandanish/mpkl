<?php

class Notification extends Model
{
    // Membership and role come from the database, never request parameters or cached departments.
    private function visibility($userId)
    {
        $user = (new User())->getById((int) $userId);
        if (!$user) { return '1 = 0'; }
        if ($user['role'] === 'admin') { return '1 = 1'; }
        $department = isset($user['department']) ? $user['department'] : null;
        $rule = "n.audience IN ('legacy', 'global')";
        if (Departments::valid($department)) {
            // Only canonical server-defined values are interpolated here.
            $rule .= " OR (n.audience = 'department' AND n.department = '" . $department . "')";
        }
        return '(' . $rule . ')';
    }

    public function getById($id, $userId = null)
    {
        if ($userId === null) { $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; }
        $database = Database::openConnection();
        $database->prepare('SELECT n.* FROM notifications n WHERE n.id = :id AND ' . $this->visibility($userId) . ' LIMIT 1');
        $database->bindValue(':id', (int) $id);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function create($data)
    {
        $audience = 'global';
        $department = null;
        $type = isset($data['entity_type']) ? $data['entity_type'] : null;
        $entityId = isset($data['entity_id']) ? (int) $data['entity_id'] : null;
        if (in_array($type, ['project', 'project_payment_schedule'], true) || $data['category'] === 'project') {
            $database = Database::openConnection();
            $query = $type === 'project_payment_schedule'
                ? 'SELECT p.department FROM projects p JOIN project_payment_schedules s ON s.project_id = p.id WHERE s.id = :id'
                : 'SELECT department FROM projects WHERE id = :id';
            $database->prepare($query);
            $database->bindValue(':id', $entityId);
            $database->execute();
            $project = $database->fetchAssociative();
            $department = $project && Departments::valid($project['department']) ? $project['department'] : null;
            $audience = $department === null ? 'admin' : 'department';
        }
        $reminderKey = in_array($data['action'], ['project_overdue', 'project_payment_overdue', 'project_payment_due_today', 'project_payment_due_soon'], true)
            ? $data['action'] . ':' . $type . ':' . $entityId : null;
        $database = Database::openConnection();
        $database->prepare('INSERT INTO notifications
            (category, action, title, message, entity_type, entity_id, audience, department, reminder_key)
            VALUES (:category, :action, :title, :message, :entity_type, :entity_id, :audience, :department, :reminder_key)
            ON DUPLICATE KEY UPDATE id = id');
        foreach (['category', 'action', 'title', 'message'] as $field) {
            $value = $data[$field];
            // Existing notification storage has a 255-character message limit.
            if ($field === 'message' && $audience !== 'global') { $value = mb_substr($value, 0, 255, 'UTF-8'); }
            $database->bindValue(':' . $field, $value);
        }
        foreach (['entity_type' => $type, 'entity_id' => $entityId, 'audience' => $audience,
            'department' => $department, 'reminder_key' => $reminderKey] as $field => $value) {
            $database->bindValue(':' . $field, $value);
        }
        return $database->execute();
    }

    public function getUnreadCount($userId)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT COUNT(*) AS total FROM notifications n
            LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = :user_id
            WHERE nr.id IS NULL AND ' . $this->visibility($userId));
        $database->bindValue(':user_id', (int) $userId);
        $database->execute();
        return (int) $database->fetchAssociative()['total'];
    }

    public function markAsRead($notificationId, $userId)
    {
        if (!$this->getById($notificationId, $userId)) { return false; }
        $database = Database::openConnection();
        $database->prepare('INSERT IGNORE INTO notification_reads (notification_id, user_id)
            SELECT n.id, :user_id FROM notifications n WHERE n.id = :id AND ' . $this->visibility($userId));
        $database->bindValue(':user_id', (int) $userId);
        $database->bindValue(':id', (int) $notificationId);
        return $database->execute();
    }

    public function markAllAsRead($userId)
    {
        $database = Database::openConnection();
        $database->prepare('INSERT IGNORE INTO notification_reads (notification_id, user_id)
            SELECT n.id, :user_id FROM notifications n WHERE ' . $this->visibility($userId));
        $database->bindValue(':user_id', (int) $userId);
        return $database->execute();
    }

    private function listForUser($userId, $recent)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT n.*, CASE WHEN nr.id IS NULL THEN 0 ELSE 1 END AS is_read
            FROM notifications n LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id AND nr.user_id = :user_id
            WHERE ' . $this->visibility($userId) . ' ORDER BY n.created_at DESC, n.id DESC' . ($recent ? ' LIMIT 10' : ''));
        $database->bindValue(':user_id', (int) $userId);
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function getAllForUser($userId) { return $this->listForUser($userId, false); }
    public function getRecentForUser($userId) { return $this->listForUser($userId, true); }

    public function clearAll()
    {
        $database = Database::openConnection();

        $database->prepare("
        DELETE FROM notification_reads
    ");

        $database->execute();

        $database->prepare("
        DELETE FROM notifications
    ");

        return $database->execute();
    }
    public function existsForEntity(
        $action,
        $entityType,
        $entityId
    ) {
        $database = Database::openConnection();

        $database->prepare("
        SELECT id
        FROM notifications

        WHERE action = :action
        AND entity_type = :entity_type
        AND entity_id = :entity_id

        LIMIT 1
    ");

        $database->bindValue(
            ':action',
            $action
        );

        $database->bindValue(
            ':entity_type',
            $entityType
        );

        $database->bindValue(
            ':entity_id',
            $entityId
        );

        $database->execute();

        $result =
            $database->fetchAssociative();

        return $result ? true : false;
    }
    public function generateChargePaymentReminders(
        $developmentChargeModel
    ) {
        $payments =
            $developmentChargeModel
                ->getPendingPaymentsWithDueDate();


        if (empty($payments)) {
            return;
        }


        $today =
            new DateTime(
                date('Y-m-d')
            );


        foreach ($payments as $payment) {

            if (empty($payment['due_date'])) {
                continue;
            }


            $dueDate =
                new DateTime(
                    $payment['due_date']
                );


            $difference =
                (int) $today
                    ->diff($dueDate)
                    ->format('%r%a');


            /*
            |--------------------------------------------------------------------------
            | OVERDUE
            |--------------------------------------------------------------------------
            */

            if ($difference < 0) {

                $action =
                    'charge_payment_overdue';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Tertunggak',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' telah melepasi tarikh akhir ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $payment['due_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id']
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DUE TODAY
            |--------------------------------------------------------------------------
            */

            if ($difference === 0) {

                $action =
                    'charge_payment_due_today';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Perlu Dibayar Hari Ini',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' perlu dibayar hari ini.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id']
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | WITHIN 7 DAYS
            |--------------------------------------------------------------------------
            */

            if (
                $difference > 0 &&
                $difference <= 7
            ) {

                $action =
                    'charge_payment_due_soon';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Akan Tiba',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' perlu dibayar pada ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $payment['due_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id']
                    ]);

                }

            }

        }
    }

    private function validReminderDate($value)
    {
        if (!is_string($value)) { return false; }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : false;
    }

    public function generateProjectOverdueReminders($projectModel)
    {
        foreach ($projectModel->getOverdueProjects() as $project) {
            if (!$this->validReminderDate($project['end_date']) || $project['end_date'] >= date('Y-m-d')) { continue; }
            if ($this->existsForEntity('project_overdue', 'project', $project['id'])) { continue; }
            $this->create([
                'category' => 'project', 'action' => 'project_overdue', 'title' => 'Projek Melebihi Tempoh',
                'message' => 'Projek ' . $project['project_name'] . ' di bawah Jabatan ' . $project['department']
                    . ' telah melebihi tarikh tamat ' . date('d/m/Y', strtotime($project['end_date'])) . '.',
                'entity_type' => 'project', 'entity_id' => $project['id']
            ]);
        }
    }

    public function generateProjectPaymentReminders($projectModel)
    {
        $today = new DateTime(date('Y-m-d'));
        foreach ($projectModel->getPendingSchedulesWithDueDate() as $schedule) {
            $dueDate = $this->validReminderDate($schedule['claim_date']);
            if (!$dueDate) { continue; }
            $difference = (int) $today->diff($dueDate)->format('%r%a');
            if ($difference > 7) { continue; }
            if ($difference < 0) {
                $action = 'project_payment_overdue';
                $title = 'Tuntutan Projek Tertunggak';
                $ending = ' telah melepasi tarikh ' . $dueDate->format('d/m/Y') . '.';
            } elseif ($difference === 0) {
                $action = 'project_payment_due_today';
                $title = 'Tuntutan Projek Perlu Dibayar Hari Ini';
                $ending = ' perlu dibayar hari ini.';
            } else {
                $action = 'project_payment_due_soon';
                $title = 'Tuntutan Projek Akan Tiba';
                $ending = ' dijadualkan pada ' . $dueDate->format('d/m/Y') . '.';
            }
            if ($this->existsForEntity($action, 'project_payment_schedule', $schedule['id'])) { continue; }
            $this->create(['category' => 'payment', 'action' => $action, 'title' => $title,
                'message' => $schedule['project_code'] . ' - Tuntutan bernilai RM ' . number_format($schedule['amount'], 2) . $ending,
                'entity_type' => 'project_payment_schedule', 'entity_id' => $schedule['id']]);
        }
    }
}
