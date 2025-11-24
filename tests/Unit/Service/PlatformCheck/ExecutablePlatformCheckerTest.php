<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\PlatformCheck;

use PHPUnit\Framework\TestCase;
use Shared\Service\PlatformCheck\ExecutablePlatformChecker;

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
