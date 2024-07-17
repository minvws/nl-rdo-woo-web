<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\PlatformCheck;

use App\Service\PlatformCheck\ExecutablePlatformChecker;
use PHPUnit\Framework\TestCase;

class ExecutablePlatformCheckerTest extends TestCase
{
    public function testChecker(): void
    {
        $checker = new ExecutablePlatformChecker();
        $results = $checker->getResults(['/usr/bin/ls', '/foo/bar']);

        self::assertCount(2, $results);
        self::assertTrue($results[0]->successful);
        self::assertFalse($results[1]->successful);
    }
}
