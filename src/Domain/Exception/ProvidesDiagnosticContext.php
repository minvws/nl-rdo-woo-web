<?php

declare(strict_types=1);

namespace Shared\Domain\Exception;

use Throwable;

/**
 * @phpstan-type Primitive scalar|null
 * @phpstan-type Depth5 array<string, Primitive>
 * @phpstan-type Depth4 array<string, Primitive|Depth5>
 * @phpstan-type Depth3 array<string, Primitive|Depth4>
 * @phpstan-type Depth2 array<string, Primitive|Depth3>
 * @phpstan-type DiagnosticContext array<string, Primitive|Depth2>
 */
interface ProvidesDiagnosticContext extends Throwable
{
    /**
     * @return DiagnosticContext
     */
    public function getDiagnosticContext(): array;
}
