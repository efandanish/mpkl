<?php

/** Validates new automatic schedules and preserves existing manual/historical edits. */
class ChargeValidation
{
    public static function text($value)
    {
        return is_string($value) ? trim($value) : '';
    }

    public static function positiveAmount($value)
    {
        // DECIMAL(12,2), no exponent, thousands separator, overflow or silent rounding.
        return (is_string($value) || is_int($value) || is_float($value))
            && preg_match('/\A\d{1,10}(?:\.\d{1,2})?\z/D', (string) $value)
            && (float) $value > 0;
    }

    public static function date($value)
    {
        if (!is_string($value) || !preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/D', $value, $parts)) {
            return false;
        }
        return (int) $parts[1] >= 1000 && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    public static function validate(array $input, array $existing = null, array $payments = [])
    {
        $errors = [];
        $data = [];
        $fields = [
            'developer_name' => ['Nama Pemaju', 200], 'project_name' => ['Nama Projek', 255],
            'osc_id' => ['ID OSC', 100], 'lot_number' => ['No. Lot/PT', 100],
            'mukim' => ['Mukim', 150], 'file_number' => ['No. Fail', 150]
        ];
        foreach ($fields as $key => $field) {
            $data[$key] = self::text(isset($input[$key]) ? $input[$key] : null);
            if ($data[$key] === '' || mb_strlen($data[$key], 'UTF-8') > $field[1]) {
                $errors[] = $field[0] . ' wajib diisi (maksimum ' . $field[1] . ' aksara).';
            }
        }
        $data['charge_amount'] = self::text(isset($input['charge_amount']) ? (is_scalar($input['charge_amount']) ? (string) $input['charge_amount'] : null) : null);
        if (!self::positiveAmount($data['charge_amount'])) {
            $errors[] = 'Jumlah Caj mesti nombor positif, maksimum 10 digit sebelum titik dan 2 tempat perpuluhan.';
        }
        foreach (['osc_decision' => ['Lulus', 'Tangguh', 'Ditolak'], 'payment_plan' => ['Bayaran Penuh', '3 Ansuran', '4 Ansuran']] as $key => $values) {
            $data[$key] = self::text(isset($input[$key]) ? $input[$key] : null);
            if (!in_array($data[$key], $values, true)) {
                $errors[] = ($key === 'osc_decision' ? 'Keputusan OSC' : 'Pelan Bayaran') . ' tidak sah.';
            }
        }
        $paid = array_filter($payments, function ($p) { return $p['status'] === 'Dibayar'; });
        $changedPlan = $existing && $data['payment_plan'] !== $existing['payment_plan'];
        if ($paid && $changedPlan) {
            $errors[] = 'Pelan bayaran tidak boleh diubah selepas bayaran direkodkan.';
        }
        if ($paid && self::positiveAmount($data['charge_amount'])
            && ChargeInstallments::cents($data['charge_amount']) !== ChargeInstallments::cents($existing['charge_amount'])) {
            $errors[] = 'Jumlah Caj tidak boleh diubah selepas bayaran direkodkan.';
        }
        $new = [];
        $updates = [];
        $count = $data['payment_plan'] === '3 Ansuran' ? 3 : ($data['payment_plan'] === '4 Ansuran' ? 4 : 1);
        if (!$existing && $data['payment_plan'] === '3 Ansuran') {
            try {
                $calculated = ChargeInstallments::calculate($data['charge_amount'], isset($input['first_installment_due_date']) ? $input['first_installment_due_date'] : null);
                $new = $calculated['rows'];
                // Preview fields may be omitted; when present, every derived value must match.
                $keys = ['installment_no', 'installment_amount', 'installment_due_date'];
                if (self::hasAny($input, $keys)) {
                    foreach ($keys as $column => $key) {
                        if (!isset($input[$key]) || !is_array($input[$key]) || array_keys($input[$key]) !== [0, 1, 2]) {
                            $errors[] = 'Butiran ansuran automatik tidak lengkap atau telah diubah. Sila kira semula.';
                            continue;
                        }
                        foreach ($new as $index => $row) {
                            $value = $input[$key][$index];
                            $matches = (is_string($value) || is_int($value)) && ($column === 1
                                ? ChargeInstallments::cents($value) === ChargeInstallments::cents($row[$column])
                                : (string) $value === (string) $row[$column]);
                            if (!$matches) { $errors[] = 'Jumlah atau tarikh ansuran automatik telah diubah. Sila kira semula.'; }
                        }
                    }
                }
            } catch (InvalidArgumentException $error) {
                $errors[] = $error->getMessage();
            }
        } elseif (!$existing || $changedPlan) {
            $prefix = $existing ? 'new_installment_' : 'installment_';
            if ($count > 1 || ($existing && isset($input[$prefix . 'no']))) {
                $new = self::rows($input, [$prefix . 'no', $prefix . 'amount', $prefix . 'due_date'], $errors);
                if (count($new) !== $count) {
                    $errors[] = 'Bilangan butiran ansuran mesti sepadan dengan pelan bayaran.';
                }
                foreach ($new as $index => $row) {
                    if ((string) $row[0] !== (string) ($index + 1)) {
                        $errors[] = 'Nombor ansuran mesti mengikut urutan bermula daripada 1.';
                    }
                }
            } else {
                // Existing full-payment creation has no deadline input. Preserve that convention.
                $new = [[1, $data['charge_amount'], null]];
                foreach (['installment_no', 'installment_amount', 'installment_due_date'] as $key) {
                    if (isset($input[$key])) { $errors[] = 'Butiran ansuran tidak sepadan dengan pelan bayaran penuh.'; break; }
                }
            }
        }
        if ($changedPlan && self::hasAny($input, ['payment_schedule_id', 'payment_schedule_amount', 'payment_schedule_due_date'])) {
            $errors[] = 'Jangan sertakan jadual lama apabila menukar pelan bayaran.';
        }
        if ($existing && !$changedPlan) {
            $pending = [];
            foreach ($payments as $p) {
                if ($p['status'] === 'Menunggu') { $pending[(string) $p['id']] = $p; }
            }
            $keys = ['payment_schedule_id', 'payment_schedule_amount', 'payment_schedule_due_date'];
            if ($pending || self::hasAny($input, $keys)) {
                $boundaryZero = $data['payment_plan'] === '3 Ansuran' && self::positiveAmount($data['charge_amount'])
                    && ChargeInstallments::cents($data['charge_amount']) === 10000000;
                $updates = self::rows($input, $keys, $errors, $pending, $boundaryZero);
                $seen = [];
                foreach ($updates as $row) {
                    $id = is_scalar($row[0]) ? (string) $row[0] : '';
                    if (!isset($pending[$id]) || isset($seen[$id])) {
                        $errors[] = 'Jadual tidak sah, berulang, telah dibayar atau bukan milik caj ini.';
                    }
                    $seen[$id] = true;
                }
                if (count($seen) !== count($pending)) { $errors[] = 'Semua butiran ansuran belum dibayar wajib disertakan.'; }
            }
            if (self::hasAny($input, ['new_installment_no', 'new_installment_amount', 'new_installment_due_date'])) {
                $errors[] = 'Jadual baharu hanya dibenarkan apabila pelan bayaran berubah.';
            }
        }
        if ($existing && self::hasAny($input, ['installment_no', 'installment_amount', 'installment_due_date'])) {
            $errors[] = 'Struktur butiran ansuran untuk kemaskini tidak sah.';
        }
        if ($existing && !$errors) {
            $updates = self::reconcileEdit($data, $existing, $payments, $new, $updates, $errors);
        }
        return ['data' => $data, 'errors' => array_values(array_unique($errors)), 'new' => $new, 'updates' => $updates];
    }

    /** Compare exact principal, permitting only identical unparseable historical values. */
    private static function samePrincipalAmount($left, $right)
    {
        try { return ChargeInstallments::cents($left) === ChargeInstallments::cents($right); }
        catch (InvalidArgumentException $error) {
            return (is_string($left) || is_int($left)) && (string) $left === (string) $right;
        }
    }

    private static function scheduleMatches(array $rows, $amount, $plan)
    {
        $counts = ['Bayaran Penuh' => 1, '3 Ansuran' => 3, '4 Ansuran' => 4];
        if (!isset($counts[$plan]) || count($rows) !== $counts[$plan]) { return false; }
        $seen = [];
        $sum = 0;
        try {
            foreach ($rows as $row) {
                $number = (int) $row['installment_no'];
                if ($number < 1 || $number > $counts[$plan] || isset($seen[$number])) { return false; }
                $seen[$number] = true;
                $sum += ChargeInstallments::cents($row['amount']);
            }
            return $sum === ChargeInstallments::cents($amount);
        } catch (InvalidArgumentException $error) { return false; }
    }

    /** Reconcile the whole proposed principal, including immutable paid rows. Never recalculate. */
    private static function reconcileEdit(array $data, array $existing, array $payments, array $new, array $updates, array &$errors)
    {
        $changedPlan = $data['payment_plan'] !== $existing['payment_plan'];
        $financialChange = $changedPlan || !self::samePrincipalAmount($data['charge_amount'], $existing['charge_amount']);
        $byId = [];
        foreach ($payments as $row) { $byId[(string) $row['id']] = $row; }
        $changedUpdates = [];
        foreach ($updates as $row) {
            // Ownership, completeness and duplicate IDs have already been validated.
            $old = $byId[(string) $row[0]];
            if (!self::samePrincipalAmount($row[1], $old['amount']) || $row[2] !== $old['due_date']) {
                $financialChange = true;
                $changedUpdates[] = $row;
            }
            $byId[(string) $row[0]]['amount'] = $row[1];
        }
        if ($financialChange) {
            foreach ($payments as $row) {
                if (!in_array($row['status'], ['Menunggu', 'Dibayar'], true)) {
                    $errors[] = 'Perubahan kewangan tidak boleh disimpan kerana status ansuran lama tidak dapat disahkan. Sila semak rekod lama tanpa mengubah sejarah bayaran.';
                    return [];
                }
            }
        }
        $proposed = array_values($byId);
        if ($changedPlan) {
            $proposed = [];
            foreach ($new as $row) { $proposed[] = ['installment_no' => $row[0], 'amount' => $row[1]]; }
        }
        // No age/formula marker exists. Preserve unchanged legacy discrepancies, not new ones.
        if ($financialChange || self::scheduleMatches($payments, $existing['charge_amount'], $existing['payment_plan'])) {
            if (!self::scheduleMatches($proposed, $data['charge_amount'], $data['payment_plan'])) {
                $errors[] = 'Jumlah prinsipal ansuran mesti sepadan dengan Jumlah Caj. Sila lengkapkan jadual mengikut pelan bayaran dan selaraskan jumlah ansuran sebelum menyimpan. Bunga anggaran 8% tidak termasuk dalam prinsipal.';
            }
        }
        // Unchanged historical rows must not be rewritten during a metadata-only edit.
        return $changedUpdates;
    }

    private static function hasAny(array $input, array $keys)
    {
        foreach ($keys as $key) { if (array_key_exists($key, $input)) { return true; } }
        return false;
    }

    private static function rows(array $input, array $keys, array &$errors, array $pending = [], $allowBoundaryZero = false)
    {
        foreach ($keys as $key) {
            if (!isset($input[$key]) || !is_array($input[$key]) || count($input[$key]) > 1000) {
                $errors[] = 'Struktur butiran ansuran tidak lengkap atau tidak sah.';
                return [];
            }
        }
        $indices = array_keys($input[$keys[0]]);
        if ($indices !== array_keys($input[$keys[1]]) || $indices !== array_keys($input[$keys[2]])) {
            $errors[] = 'Setiap ansuran memerlukan nombor, jumlah dan tarikh yang sepadan.';
            return [];
        }
        $rows = [];
        foreach ($indices as $index) {
            $id = $input[$keys[0]][$index];
            $amount = $input[$keys[1]][$index];
            $date = $input[$keys[2]][$index];
            // A metadata-only edit may preserve even an invalid historical value verbatim.
            if ((is_string($id) || is_int($id)) && isset($pending[(string) $id])
                && self::samePrincipalAmount($amount, $pending[(string) $id]['amount'])
                && ($date === $pending[(string) $id]['due_date']
                    || ($date === '' && !self::date($pending[(string) $id]['due_date'])))) {
                $rows[] = [$id, $pending[(string) $id]['amount'], $pending[(string) $id]['due_date']];
                continue;
            }
            // Do not force a historical NULL deadline into an invented date on unrelated edits.
            $legacyNull = is_scalar($id) && isset($pending[(string) $id])
                && $pending[(string) $id]['due_date'] === null && $date === '';
            // Preserve legitimate zero-principal rows at the RM100,000 boundary on edits.
            $unchangedZero = is_scalar($id) && isset($pending[(string) $id])
                && $pending[(string) $id]['amount'] === '0.00'
                && (is_string($amount) || is_int($amount)) && preg_match('/\A0{1,10}(?:\.0{1,2})?\z/D', (string) $amount);
            // An explicit correction to the Problem 4 RM100,000 boundary has zero rows 2/3.
            $boundaryZero = $allowBoundaryZero && is_scalar($id) && isset($pending[(string) $id])
                && in_array((int) $pending[(string) $id]['installment_no'], [2, 3], true)
                && (is_string($amount) || is_int($amount)) && preg_match('/\A0{1,10}(?:\.0{1,2})?\z/D', (string) $amount);
            if ((!is_string($id) && !is_int($id)) || !ctype_digit((string) $id) || (int) $id < 1
                || (!self::positiveAmount($amount) && !$unchangedZero && !$boundaryZero) || (!$legacyNull && !self::date($date))) {
                $errors[] = 'Setiap ansuran mesti mempunyai nombor sah, jumlah positif dan tarikh kalendar sah (YYYY-MM-DD).';
            }
            $rows[] = [$id, $amount, $legacyNull ? null : $date];
        }
        return $rows;
    }
}
