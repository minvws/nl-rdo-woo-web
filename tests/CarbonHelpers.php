<?php

declare(strict_types=1);

namespace Shared\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Before;

trait CarbonHelpers
{
    /**
     * @param \DateTimeInterface|\Closure|string|Carbon|CarbonImmutable|false|null $testNow real or mock Carbon instance
     */
    public static function setTestNow(mixed $testNow = null): void
    {
        Carbon::setTestNow($testNow);
        CarbonImmutable::setTestNow($testNow);
    }

    #[Before]
    protected function resetCarbon(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
    }
}
