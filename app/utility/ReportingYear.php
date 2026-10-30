<?php

/** Navigation/record context only; never derives a cash receipt or project start year. */
class ReportingYear
{
    public static function valid($value)
    {
        return (is_string($value) || is_int($value))
            && preg_match('/\A[1-9][0-9]{3}\z/D', (string) $value);
    }

    public static function capture(array $query, array $post)
    {
        $values = [];
        foreach ([$query, $post] as $source) {
            if (array_key_exists('year', $source)) {
                if (!self::valid($source['year'])) {
                    throw new InvalidArgumentException('Tahun mesti empat digit antara 1000 hingga 9999.');
                }
                $values[] = (int) $source['year'];
            }
        }
        if (count(array_unique($values)) > 1) {
            throw new InvalidArgumentException('Tahun dalam pautan dan borang tidak sepadan. Muat semula borang.');
        }
        $_SESSION['selected_year'] = $values ? $values[0] : self::selected();
        return $_SESSION['selected_year'];
    }

    public static function selected()
    {
        return isset($_SESSION['selected_year']) && self::valid($_SESSION['selected_year'])
            ? (int) $_SESSION['selected_year'] : (int) date('Y');
    }

    public static function suffix()
    {
        return '?year=' . self::selected();
    }

    public static function url($path)
    {
        return PUBLIC_ROOT . $path . (strpos($path, '?') === false ? '?' : '&') . 'year=' . self::selected();
    }
}
