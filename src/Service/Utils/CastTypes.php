<?php

declare(strict_types=1);

namespace Shared\Service\Utils;

use DateTimeImmutable;
use Exception;

use function is_null;
use function is_string;
use function trim;

final readonly class CastTypes
{
    public static function asImmutableDate(mixed $value, ?string $format = null): ?DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            if (is_null($format)) {
                return new DateTimeImmutable($value);
            }

            $date = DateTimeImmutable::createFromFormat($format, $value);

            if ($date === false) {
                return null;
            }

            return $date;
        } catch (Exception) {
            return null;
        }
    }
}
