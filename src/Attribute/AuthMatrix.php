<?php

declare(strict_types=1);

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class AuthMatrix
{
    public function __construct(public string $permission)
    {
    }
}
