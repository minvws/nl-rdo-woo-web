<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\PlatformCheck;

use PHPUnit\Framework\TestCase;
use Shared\Service\PlatformCheck\PhpExtensionPlatformChecker;

class PhpExtensionPlatformCheckerTest extends TestCase
{
    public function testChecker(): void
    {
        $checker = new PhpExtensionPlatformChecker();
        $results = $checker->getResults(['json', 'foo']);

        self::assertCount(2, $results);
        self::assertTrue($results[0]->successful);
        self::assertFalse($results[1]->successful);
    }
}
