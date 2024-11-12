<?php

declare(strict_types=1);

namespace App\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Before;

trait ResetCarbon
{
    #[Before]
    protected function resetCarbon(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }
}
