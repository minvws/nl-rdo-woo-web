<?php

declare(strict_types=1);

namespace App;

use App\Entity\EntityWithFileInfo;

class Utils
{
    public static function size(string|int $value): string
    {
        $value = (int) $value;

        if ($value < 1024) {
            return $value . ' bytes';
        }

        if ($value < 1048576) {
            return round($value / 1024, 2) . ' KB';
        }

        if ($value < 1073741824) {
            return round($value / 1048576, 2) . ' MB';
        }

        return round($value / 1073741824, 2) . ' GB';
    }

    public static function getFileSize(EntityWithFileInfo $entity): string
    {
        return self::size(
            $entity->getFileInfo()->getSize()
        );
    }
}
