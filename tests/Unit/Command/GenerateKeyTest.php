<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\GenerateKey;
use Shared\Service\Encryption\EncryptionService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateKeyTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $mapping = $this->getFaker()->word();

        $encryptionService = Mockery::mock(EncryptionService::class);

        $command = new GenerateKey($encryptionService);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
