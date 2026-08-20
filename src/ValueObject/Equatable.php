<?php

declare(strict_types=1);

namespace Shared\ValueObject;

/**
 * @template T of Equatable
 */
interface Equatable
{
    /**
     * @param T $other
     */
    public function equalTo(Equatable $other): bool;
}
