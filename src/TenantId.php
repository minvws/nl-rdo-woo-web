<?php

declare(strict_types=1);

namespace Shared;

use function array_map;
use function implode;
use function strtolower;

enum TenantId: string
{
    case MINVWS = 'minvws';

    case MINFIN = 'minfin';

    public static function fromString(string $value): self
    {
        return self::from(strtolower($value));
    }

    public static function asString(): string
    {
        $list = array_map(static fn (self $tenant): string => $tenant->value, self::cases());

        return implode(', ', $list);
    }
}
