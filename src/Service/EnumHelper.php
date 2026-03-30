<?php

declare(strict_types=1);

namespace Shared\Service;

use BackedEnum;
use Webmozart\Assert\Assert;

use function array_map;

class EnumHelper
{
    /**
     * @param array<BackedEnum> $backedEnums
     *
     * @return array<string>
     */
    public static function getStringValues(array $backedEnums): array
    {
        return array_map(static function (BackedEnum $enum): string {
            $value = $enum->value;
            Assert::string($value);

            return $value;
        }, $backedEnums);
    }
}
