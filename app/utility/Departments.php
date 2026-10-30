<?php

/** Canonical stored values shared by user and project forms. */
class Departments
{
    public static function options()
    {
        return [
            'Jabatan Kejuruteraan',
            'Jabatan Khidmat Pengurusan',
            'Jabatan Landskap',
            'Jabatan Perancang Bandar'
        ];
    }

    public static function valid($value)
    {
        return is_string($value) && in_array($value, self::options(), true);
    }
}
