<?php

/** Principal-only calculator for newly created 3 Ansuran charges (64-bit PHP). */
class ChargeInstallments
{
    public static function cents($amount)
    {
        if (PHP_INT_SIZE < 8 || (!is_string($amount) && !is_int($amount))
            || !preg_match('/\A(\d{1,10})(?:\.(\d{1,2}))?\z/D', (string) $amount, $parts)) {
            throw new InvalidArgumentException('Jumlah Caj mesti nombor sah dengan maksimum 2 tempat perpuluhan.');
        }
        return (int) $parts[1] * 100 + (int) str_pad(isset($parts[2]) ? $parts[2] : '', 2, '0');
    }

    public static function money($cents)
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function principal($amount)
    {
        $total = self::cents($amount);
        if ($total < 10000000) {
            throw new InvalidArgumentException('Pelan 3 Ansuran memerlukan Jumlah Caj sekurang-kurangnya RM100,000.00. Sila pilih Bayaran Penuh bagi jumlah yang lebih rendah.');
        }
        if ($total <= 20000000) {
            $first = 10000000;
            $second = intdiv($total - $first + 1, 2);
        } else {
            $first = intdiv($total + 1, 2);
            $second = intdiv($total + 2, 4);
        }
        $third = $total - $first - $second;
        return [
            'amounts' => array_map([self::class, 'money'], [$first, $second, $third]),
            'interest' => self::money(intdiv(($total - $first) * 8 + 50, 100))
        ];
    }

    public static function addThreeMonths($date)
    {
        if (!ChargeValidation::date($date)) {
            throw new InvalidArgumentException('Tarikh Akhir Ansuran 1 wajib diisi dengan tarikh kalendar sah (YYYY-MM-DD).');
        }
        $parts = array_map('intval', explode('-', $date));
        $monthIndex = $parts[0] * 12 + $parts[1] - 1 + 3;
        $year = intdiv($monthIndex, 12);
        $month = $monthIndex % 12 + 1;
        if ($year > 9999) { throw new InvalidArgumentException('Tarikh ansuran terjana mesti dalam julat tahun 1000 hingga 9999.'); }
        $first = DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%04d-%02d-01', $year, $month));
        return sprintf('%04d-%02d-%02d', $year, $month, min($parts[2], (int) $first->format('t')));
    }

    public static function calculate($amount, $firstDeadline)
    {
        $result = self::principal($amount);
        $second = self::addThreeMonths($firstDeadline);
        $third = self::addThreeMonths($second);
        $result['rows'] = [
            [1, $result['amounts'][0], $firstDeadline],
            [2, $result['amounts'][1], $second],
            [3, $result['amounts'][2], $third]
        ];
        return $result;
    }
}
