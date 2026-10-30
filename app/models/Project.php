<?php

class Project extends Model
{
    public function getAll()
    {
        $database = Database::openConnection();

        $database->prepare("
            SELECT *
            FROM projects
            ORDER BY created_at DESC
        ");

        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function lockById($id)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM projects WHERE id = :id FOR UPDATE');
        $database->bindValue(':id', $id);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function getById($id)
    {
        $database = Database::openConnection();

        $database->prepare("
            SELECT *
            FROM projects
            WHERE id = :id
            LIMIT 1
        ");

        $database->bindValue(':id', $id);
        $database->execute();

        return $database->fetchAssociative();
    }


    public function projectIdExists($projectId)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT id FROM projects WHERE project_id = :project_id LIMIT 1');
        $database->bindValue(':project_id', $projectId);
        $database->execute();
        return (bool) $database->fetchAssociative();
    }

    public function getUndated()
    {
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM projects WHERE start_date IS NULL ORDER BY created_at DESC');
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function create($data)
    {
        $database = Database::openConnection();

        $query = "
        INSERT INTO projects (
            project_id,
            department,
            project_name,
            description,
            approval_status,
            meeting_count,
            procurement_type,
            sst_value,
            meeting_approval_value,
            payment_method,
            start_date,
            end_date,
            project_status
        )
        VALUES (
            :project_id,
            :department,
            :project_name,
            :description,
            :approval_status,
            :meeting_count,
            :procurement_type,
            :sst_value,
            :meeting_approval_value,
            :payment_method,
            :start_date,
            :end_date,
            :project_status
        )
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $data['project_id']
        );

        $database->bindValue(
            ':department',
            $data['department']
        );

        $database->bindValue(
            ':project_name',
            $data['project_name']
        );

        $database->bindValue(
            ':description',
            $data['description']
        );

        $database->bindValue(
            ':approval_status',
            $data['approval_status']
        );

        $database->bindValue(
            ':meeting_count',
            $data['meeting_count']
        );

        $database->bindValue(
            ':procurement_type',
            $data['procurement_type']
        );

        $database->bindValue(
            ':sst_value',
            $data['sst_value']
        );

        $database->bindValue(
            ':meeting_approval_value',
            $data['meeting_approval_value']
        );

        $database->bindValue(
            ':payment_method',
            $data['payment_method']
        );

        $database->bindValue(
            ':start_date',
            $data['start_date'] ?: null
        );

        $database->bindValue(
            ':end_date',
            $data['end_date'] ?: null
        );

        $database->bindValue(
            ':project_status',
            $data['project_status']
        );

        $database->execute();

        if ($database->countRows() !== 1) {
            return false;
        }

        return $database->lastInsertId();
    }


    public function update($id, $data)
    {
        $database = Database::openConnection();

        $database->prepare("
            UPDATE projects
            SET
                project_id = :project_id,
                department = :department,
                project_name = :project_name,
                description = :description,
                approval_status = :approval_status,
                meeting_count = :meeting_count,
                procurement_type = :procurement_type,
                sst_value = :sst_value,
                meeting_approval_value = :meeting_approval_value,
                payment_method = :payment_method,
                start_date = :start_date,
                end_date = :end_date,
                project_status = :project_status
            WHERE id = :id
        ");

        $database->bindValue(':project_id', $data['project_id']);
        $database->bindValue(':department', $data['department']);
        $database->bindValue(':project_name', $data['project_name']);
        $database->bindValue(':description', $data['description']);
        $database->bindValue(':approval_status', $data['approval_status']);
        $database->bindValue(':meeting_count', $data['meeting_count']);
        $database->bindValue(':procurement_type', $data['procurement_type']);
        $database->bindValue(':sst_value', $data['sst_value']);
        $database->bindValue(':meeting_approval_value', $data['meeting_approval_value']);
        $database->bindValue(':payment_method', $data['payment_method']);
        $database->bindValue(':start_date', $data['start_date'] ?: null);
        $database->bindValue(':end_date', $data['end_date'] ?: null);
        $database->bindValue(':project_status', $data['project_status']);
        $database->bindValue(':id', $id);

        return $database->execute();
    }


    public function delete($id)
    {
        $database = Database::openConnection();

        $database->prepare("
            DELETE FROM projects
            WHERE id = :id
        ");

        $database->bindValue(':id', $id);

        return $database->execute();
    }


    public function addSchedule($projectId, $date, $amount, $type)
    {
        $database = Database::openConnection();

        $database->prepare("
            INSERT INTO project_payment_schedules (
                project_id,
                claim_date,
                amount,
                type
            )
            VALUES (
                :project_id,
                :claim_date,
                :amount,
                :type
            )
        ");

        $database->bindValue(':project_id', $projectId);
        $database->bindValue(':claim_date', $date);
        $database->bindValue(':amount', $amount);
        $database->bindValue(':type', $type);

        return $database->execute();
    }


    public function getSchedules($projectId)
    {
        $database = Database::openConnection();

        $database->prepare("
            SELECT *
            FROM project_payment_schedules
            WHERE project_id = :project_id
            ORDER BY claim_date ASC
        ");

        $database->bindValue(':project_id', $projectId);
        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function deleteSchedules($projectId)
    {
        $database = Database::openConnection();

        $database->prepare("
            DELETE FROM project_payment_schedules
            WHERE project_id = :project_id
        ");

        $database->bindValue(':project_id', $projectId);

        return $database->execute();
    }

    public function scheduleHasExpenses($scheduleId)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT COUNT(*) AS total FROM project_expenses WHERE schedule_id = :id');
        $database->bindValue(':id', $scheduleId);
        $database->execute();
        return (int) $database->fetchAssociative()['total'] > 0;
    }

    public function getScheduleById($id)
    {
        $database = Database::openConnection();

        $query = "
        SELECT *
        FROM project_payment_schedules
        WHERE id = :id
        LIMIT 1
    ";

        $database->prepare($query);

        $database->bindValue(':id', $id);

        $database->execute();

        return $database->fetchAssociative();
    }


    public function addExpense(
        $projectId,
        $scheduleId,
        $amount,
        $paymentDate,
        $description
    ) {
        $database = Database::openConnection();

        $query = "
        INSERT INTO project_expenses (
            project_id,
            schedule_id,
            amount,
            payment_date,
            description
        )
        VALUES (
            :project_id,
            :schedule_id,
            :amount,
            :payment_date,
            :description
        )
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $projectId
        );

        $database->bindValue(
            ':schedule_id',
            $scheduleId
        );

        $database->bindValue(
            ':amount',
            $amount
        );

        $database->bindValue(
            ':payment_date',
            $paymentDate
        );

        $database->bindValue(
            ':description',
            $description
        );

        return $database->execute();
    }


    public function markSchedulePaid($scheduleId)
    {
        $database = Database::openConnection();

        $query = "
        UPDATE project_payment_schedules
        SET status = 'Dibayar'
        WHERE id = :id
    ";

        $database->prepare($query);

        $database->bindValue(
            ':id',
            $scheduleId
        );

        return $database->execute();
    }


    public function getExpenses($projectId)
    {
        $database = Database::openConnection();

        $query = "
        SELECT
            project_expenses.*,
            project_payment_schedules.claim_date,
            project_payment_schedules.type AS schedule_type
        FROM project_expenses

        LEFT JOIN project_payment_schedules
            ON project_expenses.schedule_id =
               project_payment_schedules.id

        WHERE project_expenses.project_id = :project_id

        ORDER BY project_expenses.payment_date DESC,
                 project_expenses.id DESC
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $projectId
        );

        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function getTotalExpenses($projectId)
    {
        $database = Database::openConnection();

        $query = "
        SELECT
            COALESCE(SUM(amount), 0) AS total
        FROM project_expenses
        WHERE project_id = :project_id
    ";

        $database->prepare($query);

        $database->bindValue(
            ':project_id',
            $projectId
        );

        $database->execute();

        $result = $database->fetchAssociative();

        if (!$result) {
            return 0;
        }

        return $result['total'];
    }
    public function getExpenseById($id)
    {
        $database = Database::openConnection();

        $query = "
        SELECT *
        FROM project_expenses
        WHERE id = :id
        LIMIT 1
    ";

        $database->prepare($query);
        $database->bindValue(':id', $id);
        $database->execute();

        return $database->fetchAssociative();
    }


    public function deleteExpense($id)
    {
        $database = Database::openConnection();

        $query = "
        DELETE FROM project_expenses
        WHERE id = :id
    ";

        $database->prepare($query);
        $database->bindValue(':id', $id);

        return $database->execute();
    }


    public function markSchedulePending($scheduleId)
    {
        $database = Database::openConnection();

        $query = "
        UPDATE project_payment_schedules
        SET status = 'Menunggu'
        WHERE id = :id
    ";

        $database->prepare($query);
        $database->bindValue(':id', $scheduleId);

        return $database->execute();
    }

    /** Retained recorded expenditure, by actual payment date rather than project cohort. */
    public function getExpenditureByPaymentYear($year)
    {
        if (!ReportingYear::valid($year)) { throw new InvalidArgumentException('Tahun perbelanjaan tidak sah.'); }
        $database = Database::openConnection();
        // No schedule/project join: each recorded expense contributes exactly once.
        // An unassignable date could belong to any year; do not present an incomplete total.
        $database->prepare("
            SELECT COALESCE(SUM(CASE WHEN in_period = 1 THEN amount ELSE 0.00 END), 0.00) AS total,
                COALESCE(SUM(CASE WHEN invalid_date = 1
                    OR (in_period = 1 AND (amount IS NULL OR amount < 0)) THEN 1 ELSE 0 END), 0) AS unverified_count
            FROM (
                SELECT amount, payment_date BETWEEN :start_date AND :end_date AS in_period,
                    CASE WHEN payment_date IS NULL OR YEAR(payment_date) < 1000
                            OR MONTH(payment_date) = 0 OR DAYOFMONTH(payment_date) = 0 THEN 1
                        WHEN LAST_DAY(payment_date) IS NULL
                            OR DAYOFMONTH(payment_date) > DAYOFMONTH(LAST_DAY(payment_date)) THEN 1
                        ELSE 0 END AS invalid_date
                FROM project_expenses
            ) recorded_expenses
        ");
        $database->bindValue(':start_date', sprintf('%04d-01-01', (int) $year));
        $database->bindValue(':end_date', sprintf('%04d-12-31', (int) $year));
        $database->execute();
        $result = $database->fetchAssociative();
        $unverified = (int) $result['unverified_count'];
        // MariaDB sums DECIMAL values. Keep the exact decimal string, including verified zero.
        return ['total' => $unverified ? null : $result['total'], 'unverified_count' => $unverified];
    }

    public function getDashboardSummaryByYear($year)
    {
        $database = Database::openConnection();
        // Match getAllByYear's cohort and getTotalExpenses's recorded expenses.
        $database->prepare("
            SELECT COUNT(DISTINCT p.id) AS record_count,
                COALESCE(SUM(e.amount), 0) AS expenditure_amount
            FROM projects p
            LEFT JOIN project_expenses e ON e.project_id = p.id
            WHERE YEAR(p.start_date) = :year
        ");
        $database->bindValue(':year', (int) $year);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function getAllByYear($year)
    {
        $database = Database::openConnection();

        $query = "
        SELECT *
        FROM projects
        WHERE YEAR(start_date) = :year
        ORDER BY created_at DESC
    ";

        $database->prepare($query);

        $database->bindValue(
            ':year',
            $year
        );

        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function addVariation(
        $projectId,
        $description,
        $type,
        $amount,
        $date
    ) {
        $database = Database::openConnection();

        $query = "
        INSERT INTO project_variations (
            project_id,
            description,
            variation_type,
            amount,
            variation_date
        )
        VALUES (
            :project_id,
            :description,
            :variation_type,
            :amount,
            :variation_date
        )
    ";

        $database->prepare($query);

        $database->bindValue(':project_id', $projectId);
        $database->bindValue(':description', $description);
        $database->bindValue(':variation_type', $type);
        $database->bindValue(':amount', $amount);
        $database->bindValue(':variation_date', $date);

        return $database->execute();
    }


    public function getVariations($projectId)
    {
        $database = Database::openConnection();

        $query = "
        SELECT *
        FROM project_variations
        WHERE project_id = :project_id
        ORDER BY variation_date DESC, id DESC
    ";

        $database->prepare($query);
        $database->bindValue(':project_id', $projectId);
        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function getVariationTotal($projectId)
    {
        $database = Database::openConnection();

        $query = "
        SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN variation_type = 'Tambah'
                            THEN amount
                        WHEN variation_type = 'Tolak'
                            THEN -amount
                        ELSE 0
                    END
                ),
                0
            ) AS total
        FROM project_variations
        WHERE project_id = :project_id
    ";

        $database->prepare($query);
        $database->bindValue(':project_id', $projectId);
        $database->execute();

        $result = $database->fetchAssociative();

        if (!$result) {
            return 0;
        }

        return $result['total'];
    }

    public function deleteSchedule($scheduleId)
    {
        $database = Database::openConnection();

        $query = "
        DELETE FROM project_payment_schedules
        WHERE id = :id
        AND status = 'Menunggu'
    ";

        $database->prepare($query);
        $database->bindValue(':id', $scheduleId);

        return $database->execute();
    }

    public function getOverdueProjects()
    {
        $database = Database::openConnection();
        $database->prepare("SELECT id, project_name, department, end_date FROM projects
            WHERE end_date IS NOT NULL AND end_date > '0000-00-00' AND end_date < :today
            AND project_status NOT IN ('Selesai', 'Dibatalkan')");
        $database->bindValue(':today', date('Y-m-d'));
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function getPendingSchedulesWithDueDate()
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT
            s.id,
            s.project_id,
            s.claim_date,
            s.amount,
            s.type,
            s.status,

            p.project_id AS project_code,
            p.project_name,
            p.department

        FROM project_payment_schedules s

        INNER JOIN projects p
            ON p.id = s.project_id

        WHERE
            s.status = 'Menunggu'
            AND s.claim_date IS NOT NULL
            AND p.project_status NOT IN ('Selesai', 'Dibatalkan')

        ORDER BY s.claim_date ASC
    ");

        $database->execute();

        return $database->fetchAllAssociative();
    }
}
