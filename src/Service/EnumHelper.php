<?php

declare(strict_types=1);

namespace Shared\Service;

use BackedEnum;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class EnumHelper
{
    /**
     * @param array<array-key, BackedEnum> $backedEnums
     *
     * @return list<string>
     */
    public static function getStringValues(array $backedEnums): array
    {
        return array_values(array_map(static function (BackedEnum $enum): string {
            $value = $enum->value;
            Assert::string($value);

            return $value;
        }, $backedEnums));
    }
}
