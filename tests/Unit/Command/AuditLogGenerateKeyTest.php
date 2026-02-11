<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Shared\Command\AuditLogGenerateKey;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class AuditLogGenerateKeyTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $command = new AuditLogGenerateKey();

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
