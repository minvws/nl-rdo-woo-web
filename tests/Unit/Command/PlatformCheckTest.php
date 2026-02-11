<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\PlatformCheck;
use Shared\Service\PlatformCheck\PlatformCheckerInterface;
use Shared\Service\PlatformCheck\PlatformCheckResult;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PlatformCheckTest extends UnitTestCase
{
    public function testPlatformCheckReturnsErrorStatusCodeIfOneCheckFails(): void
    {
        $checkerA = Mockery::mock(PlatformCheckerInterface::class);
        $checkerA->expects('getResults')
            ->andReturn([
                PlatformCheckResult::success('foo'),
                PlatformCheckResult::success('bar'),
            ]);

        $checkerB = Mockery::mock(PlatformCheckerInterface::class);
        $checkerB->expects('getResults')
            ->andReturn([
                PlatformCheckResult::error('baz', 'oops'),
            ]);

        $command = new PlatformCheck([$checkerA, $checkerB]);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(1, $commandTester->getStatusCode());
    }
}
