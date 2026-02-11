<?php

declare(strict_types=1);

namespace Shared\Service\Utils;

use Shared\Domain\Publication\EntityWithFileInfo;

use function number_format;
use function round;

class Utils
{
    public static function size(string|int $value): string
    {
        $value = (int) $value;

        return match (true) {
            $value < 1024 => $value . ' bytes',
            $value < 1048576 => round($value / 1024, 2) . ' KB',
            $value < 1073741824 => round($value / 1048576, 2) . ' MB',
            default => round($value / 1073741824, 2) . ' GB',
        };
    }

    public static function getFileSize(EntityWithFileInfo $entity): string
    {
        return self::size(
            $entity->getFileInfo()->getSize()
        );
    }

    public static function number(int $value): string
    {
        return number_format(
            num: $value,
            thousands_separator: '.',
        );
    }
}
