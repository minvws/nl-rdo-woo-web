<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\PlatformCheck;
use App\Service\PlatformCheck\PlatformCheckerInterface;
use App\Service\PlatformCheck\PlatformCheckResult;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PlatformCheckTest extends MockeryTestCase
{
    public function testPlatformCheckReturnsErrorStatusCodeIfOneCheckFails(): void
    {
        $checkerA = \Mockery::mock(PlatformCheckerInterface::class);
        $checkerA->expects('getResults')->andReturn([
            PlatformCheckResult::success('foo'),
            PlatformCheckResult::success('bar'),
        ]);

        $checkerB = \Mockery::mock(PlatformCheckerInterface::class);
        $checkerB->expects('getResults')->andReturn([
            PlatformCheckResult::error('baz', 'oops'),
        ]);

        $command = new PlatformCheck([$checkerA, $checkerB]);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(1, $commandTester->getStatusCode());
    }
}
