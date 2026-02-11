<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Logging;

use Mockery;
use RuntimeException;
use Shared\Service\Logging\LoggingHelper;
use Shared\Service\Logging\LoggingTypeInterface;
use Shared\Tests\Unit\UnitTestCase;

final class LoggingHelperTest extends UnitTestCase
{
    public function testDisableIfNotDisabled(): void
    {
        $type = $this->getFaker()->word();

        $loggingType = Mockery::mock(LoggingTypeInterface::class);
        $loggingType->expects('isDisabled')
            ->andReturn(false);
        $loggingType->expects('disable');

        $loggingHelper = new LoggingHelper([
            $type => $loggingType,
        ]);
        $loggingHelper->disable($loggingType::class);
    }

    public function testDisableIfDisabled(): void
    {
        $type = $this->getFaker()->word();

        $loggingType = Mockery::mock(LoggingTypeInterface::class);
        $loggingType->expects('isDisabled')
            ->andReturn(true);

        $loggingHelper = new LoggingHelper([
            $type => $loggingType,
        ]);
        $loggingHelper->disable($loggingType::class);
    }

    public function testDisableAll(): void
    {
        $type = $this->getFaker()->word();

        $loggingType = Mockery::mock(LoggingTypeInterface::class);
        $loggingType->expects('isDisabled')
            ->andReturn(false);
        $loggingType->expects('disable');

        $loggingHelper = new LoggingHelper([
            $type => $loggingType,
        ]);
        $loggingHelper->disableAll();
    }

    public function testRestoreIfNotDisabled(): void
    {
        $type = $this->getFaker()->word();

        $loggingType = Mockery::mock(LoggingTypeInterface::class);
        $loggingType->expects('isDisabled')
            ->andReturn(false);
        self::expectException(RuntimeException::class);

        $loggingHelper = new LoggingHelper([
            $type => $loggingType,
        ]);
        $loggingHelper->restore($loggingType::class);
    }

    public function testRestoreIfDisabled(): void
    {
        $type = $this->getFaker()->word();

        $loggingType = Mockery::mock(LoggingTypeInterface::class);
        $loggingType->expects('isDisabled')
            ->andReturn(true);
        $loggingType->expects('restore');

        $loggingHelper = new LoggingHelper([
            $type => $loggingType,
        ]);
        $loggingHelper->restore($loggingType::class);
    }

    public function testRestoreAll(): void
    {
        $type = $this->getFaker()->word();

        $loggingType = Mockery::mock(LoggingTypeInterface::class);
        $loggingType->expects('isDisabled')
            ->andReturn(true);
        $loggingType->expects('restore');

        $loggingHelper = new LoggingHelper([
            $type => $loggingType,
        ]);
        $loggingHelper->restoreAll();
    }
}
