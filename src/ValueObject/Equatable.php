<?php

declare(strict_types=1);

namespace Shared\ValueObject;

interface Equatable
{
    public function equalTo(mixed $other): bool;
}
