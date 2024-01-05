<?php

declare(strict_types=1);

namespace App;

class Utils
{
    public static function size(string $value): string
    {
        $value = (int) $value;

        if ($value < 1024) {
            return $value . ' bytes';
        } elseif ($value < 1048576) {
            return round($value / 1024, 2) . ' KB';
        } elseif ($value < 1073741824) {
            return round($value / 1048576, 2) . ' MB';
        } else {
            return round($value / 1073741824, 2) . ' GB';
        }
    }
}
