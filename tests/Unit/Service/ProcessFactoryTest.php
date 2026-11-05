<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Shared\Service\ProcessFactory;
use Shared\Tests\Unit\UnitTestCase;

use function sprintf;

final class ProcessFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $command = $this->getFaker()->word();
        $argument = $this->getFaker()->word();

        $process = new ProcessFactory()->create([$command, $argument]);

        self::assertSame(sprintf("'%s' '%s'", $command, $argument), $process->getCommandLine());
    }
}
