<?php

declare(strict_types=1);

namespace Shared\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Before;

trait CarbonHelpers
{
    #[Before]
    protected function resetCarbon(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }
}
