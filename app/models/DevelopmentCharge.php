<?php

class DevelopmentCharge extends Model
{
    public function lockById($id)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM development_charges WHERE id = :id FOR UPDATE');
        $database->bindValue(':id', $id);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function getAll()
    {
        $database = Database::openConnection();

        $query = "
            SELECT *
            FROM development_charges
            ORDER BY created_at DESC
        ";

        $database->prepare($query);
        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function getTotalCharges()
    {
        $database = Database::openConnection();

        $query = "
            SELECT COALESCE(SUM(charge_amount), 0) AS total
            FROM development_charges
        ";

        $database->prepare($query);
        $database->execute();

        $result = $database->fetchAssociative();

        return $result['total'];
    }

    /** Cash receipts use payment dates, independently of the charge registration year. */
    public function getTotalCollectionsByYear($year)
    {
        $database = Database::openConnection();
        $database->prepare("
            SELECT COALESCE(SUM(amount), 0.00) AS total
            FROM development_charge_payments
            WHERE status = 'Dibayar'
              AND payment_date >= :start_date
              AND payment_date <= :end_date
        ");
        $database->bindValue(':start_date', sprintf('%04d-01-01', (int) $year));
        $database->bindValue(':end_date', sprintf('%04d-12-31', (int) $year));
        $database->execute();
        // Keep the database DECIMAL result; no floating-point accumulation.
        return $database->fetchAssociative()['total'];
    }

    public function getProvisionalChargeCountByYear($year)
    {
        $database = Database::openConnection();
        $database->prepare("
            SELECT COUNT(*) AS total FROM development_charges
            WHERE record_year IS NULL AND YEAR(created_at) = :year
        ");
        $database->bindValue(':year', (int) $year);
        $database->execute();
        return (int) $database->fetchAssociative()['total'];
    }

    public function getDashboardSummaryByYear($year)
    {
        $database = Database::openConnection();
        // Match getAllByYear's cohort and getTotalPaid's paid-status rule.
        $database->prepare("
            SELECT COUNT(DISTINCT c.id) AS record_count,
                COALESCE(SUM(p.amount), 0) AS paid_amount
            FROM development_charges c
            LEFT JOIN development_charge_payments p
                ON p.development_charge_id = c.id AND p.status = 'Dibayar'
            WHERE COALESCE(c.record_year, YEAR(c.created_at)) = :year
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
        FROM development_charges
        WHERE COALESCE(record_year, YEAR(created_at)) = :year
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

    public function getTotalChargesByYear($year)
    {
        $database = Database::openConnection();

        $query = "
        SELECT
            COALESCE(SUM(charge_amount), 0) AS total
        FROM development_charges
        WHERE COALESCE(record_year, YEAR(created_at)) = :year
    ";

        $database->prepare($query);

        $database->bindValue(
            ':year',
            $year
        );

        $database->execute();

        $result = $database->fetchAssociative();

        if (!$result) {
            return 0;
        }

        return $result['total'];
    }

    public function create($data)
    {
        $database = Database::openConnection();

        $query = "
        INSERT INTO development_charges (
            developer_name,
            project_name,
            osc_id,
            lot_number,
            mukim,
            file_number,
            charge_amount,
            osc_decision,
            payment_plan,
            record_year
        )
        VALUES (
            :developer_name,
            :project_name,
            :osc_id,
            :lot_number,
            :mukim,
            :file_number,
            :charge_amount,
            :osc_decision,
            :payment_plan,
            :record_year
        )
    ";

        $database->prepare($query);

        $database->bindValue(
            ':developer_name',
            $data['developer_name']
        );

        $database->bindValue(
            ':project_name',
            $data['project_name']
        );

        $database->bindValue(
            ':osc_id',
            $data['osc_id']
        );

        $database->bindValue(
            ':lot_number',
            $data['lot_number']
        );

        $database->bindValue(
            ':mukim',
            $data['mukim']
        );

        $database->bindValue(
            ':file_number',
            $data['file_number']
        );

        $database->bindValue(
            ':charge_amount',
            $data['charge_amount']
        );

        $database->bindValue(
            ':osc_decision',
            $data['osc_decision']
        );

        $database->bindValue(
            ':payment_plan',
            $data['payment_plan']
        );

        $recordYear = isset($data['record_year']) ? $data['record_year'] : null;
        if ($recordYear !== null && !ReportingYear::valid($recordYear)) { throw new InvalidArgumentException('Tahun rekod tidak sah.'); }
        $database->bindValue(':record_year', $recordYear);
        $database->execute();

        if ($database->countRows() !== 1) {
            return false;
        }

        return $database->lastInsertId();
    }

    public function getById($id)
    {
        $database = Database::openConnection();

        $query = "
        SELECT *
        FROM development_charges
        WHERE id = :id
        LIMIT 1
    ";

        $database->prepare($query);

        $database->bindValue(
            ':id',
            $id
        );

        $database->execute();

        return $database->fetchAssociative();
    }


    public function update($id, $data)
    {
        $database = Database::openConnection();

        $query = "
        UPDATE development_charges
        SET
            developer_name = :developer_name,
            project_name = :project_name,
            osc_id = :osc_id,
            lot_number = :lot_number,
            mukim = :mukim,
            file_number = :file_number,
            charge_amount = :charge_amount,
            osc_decision = :osc_decision,
            payment_plan = :payment_plan
        WHERE id = :id
    ";

        $database->prepare($query);

        $database->bindValue(
            ':developer_name',
            $data['developer_name']
        );

        $database->bindValue(
            ':project_name',
            $data['project_name']
        );

        $database->bindValue(
            ':osc_id',
            $data['osc_id']
        );

        $database->bindValue(
            ':lot_number',
            $data['lot_number']
        );

        $database->bindValue(
            ':mukim',
            $data['mukim']
        );

        $database->bindValue(
            ':file_number',
            $data['file_number']
        );

        $database->bindValue(
            ':charge_amount',
            $data['charge_amount']
        );

        $database->bindValue(
            ':osc_decision',
            $data['osc_decision']
        );

        $database->bindValue(
            ':payment_plan',
            $data['payment_plan']
        );

        $database->bindValue(
            ':id',
            $id
        );

        return $database->execute();
    }


    public function delete($id)
    {
        $database = Database::openConnection();

        $query = "
        DELETE FROM development_charges
        WHERE id = :id
    ";

        $database->prepare($query);

        $database->bindValue(
            ':id',
            $id
        );

        return $database->execute();
    }

    public function addPaymentSchedule(
        $chargeId,
        $installmentNo,
        $amount,
        $dueDate
    ) {
        $database = Database::openConnection();

        $database->prepare("
        INSERT INTO development_charge_payments (
            development_charge_id,
            installment_no,
            amount,
            due_date,
            status
        )
        VALUES (
            :development_charge_id,
            :installment_no,
            :amount,
            :due_date,
            'Menunggu'
        )
    ");

        $database->bindValue(
            ':development_charge_id',
            $chargeId
        );

        $database->bindValue(
            ':installment_no',
            $installmentNo
        );

        $database->bindValue(
            ':amount',
            $amount
        );

        $database->bindValue(
            ':due_date',
            $dueDate
        );

        return $database->execute();
    }

    public function getPayments($chargeId)
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT *
        FROM development_charge_payments
        WHERE development_charge_id = :charge_id
        ORDER BY installment_no ASC
    ");

        $database->bindValue(
            ':charge_id',
            $chargeId
        );

        $database->execute();

        return $database->fetchAllAssociative();
    }

    public function getTotalPaid($chargeId)
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT
            COALESCE(SUM(amount), 0) AS total
        FROM development_charge_payments
        WHERE development_charge_id = :charge_id
        AND status = 'Dibayar'
    ");

        $database->bindValue(
            ':charge_id',
            $chargeId
        );

        $database->execute();

        $result =
            $database->fetchAssociative();

        return $result
            ? (float) $result['total']
            : 0;
    }

    public function getPaymentById($paymentId)
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT *
        FROM development_charge_payments
        WHERE id = :id
        LIMIT 1
    ");

        $database->bindValue(
            ':id',
            $paymentId
        );

        $database->execute();

        return $database->fetchAssociative();
    }
    public function markPaymentPaid(
        $paymentId,
        $paymentDate,
        $receiptFile = null,
        $receiptNumber = null
    ) {
        $database = Database::openConnection();

        $database->prepare("
        UPDATE development_charge_payments

        SET
            status = 'Dibayar',
            payment_date = :payment_date,
            receipt_file = :receipt_file,
            receipt_number = :receipt_number

        WHERE id = :id
    ");

        $database->bindValue(
            ':payment_date',
            $paymentDate
        );

        $database->bindValue(
            ':receipt_file',
            $receiptFile
        );

        $database->bindValue(
            ':id',
            $paymentId
        );

        $database->bindValue(':receipt_number', $receiptNumber);
        return $database->execute();
    }
    public function cancelPayment($paymentId)
    {
        $database = Database::openConnection();

        $database->prepare("
        UPDATE development_charge_payments

        SET
            status = 'Menunggu',
            payment_date = NULL,
            receipt_file = NULL,
            receipt_number = NULL

        WHERE id = :id
    ");

        $database->bindValue(
            ':id',
            $paymentId
        );

        return $database->execute();
    }
    public function getPendingPaymentsWithDueDate()
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT
            p.id,
            p.development_charge_id,
            p.installment_no,
            p.amount,
            p.due_date,
            p.status,

            c.osc_id,
            c.developer_name,
            c.project_name

        FROM development_charge_payments p

        INNER JOIN development_charges c
            ON c.id = p.development_charge_id

        WHERE
            p.status = 'Menunggu'
            AND p.due_date IS NOT NULL

        ORDER BY p.due_date ASC
    ");

        $database->execute();

        return $database->fetchAllAssociative();
    }
    public function updatePendingPaymentSchedule(
        $paymentId,
        $chargeId,
        $amount,
        $dueDate
    ) {
        $database = Database::openConnection();

        $database->prepare("
        UPDATE development_charge_payments

        SET
            amount = :amount,
            due_date = :due_date

        WHERE id = :id
        AND development_charge_id = :charge_id
        AND status = 'Menunggu'
    ");

        $database->bindValue(
            ':amount',
            $amount
        );

        $database->bindValue(
            ':due_date',
            $dueDate
        );

        $database->bindValue(
            ':id',
            $paymentId
        );

        $database->bindValue(
            ':charge_id',
            $chargeId
        );

        return $database->execute();
    }
    public function hasPaidPayments($chargeId)
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT COUNT(*) AS total
        FROM development_charge_payments
        WHERE development_charge_id = :charge_id
        AND status = 'Dibayar'
    ");

        $database->bindValue(
            ':charge_id',
            $chargeId
        );

        $database->execute();

        $result =
            $database->fetchAssociative();

        return $result &&
            (int) $result['total'] > 0;
    }
    public function deletePendingPayments($chargeId)
    {
        $database = Database::openConnection();

        $database->prepare("
        DELETE FROM development_charge_payments
        WHERE development_charge_id = :charge_id
        AND status = 'Menunggu'
    ");

        $database->bindValue(
            ':charge_id',
            $chargeId
        );

        return $database->execute();
    }
}
