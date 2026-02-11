<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\FileStorageCheck;
use Shared\Domain\FileStorage\Checker\FileStorageChecker;
use Shared\Domain\FileStorage\Checker\FileStorageCheckResult;
use Shared\Domain\FileStorage\Checker\FileStorageType;
use Shared\Domain\FileStorage\Checker\OrphanedPaths;
use Shared\Domain\FileStorage\Checker\PathSet;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class FileStorageCheckTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $orphanedPaths = new OrphanedPaths();
        $orphanedPaths->add(FileStorageType::DOCUMENT, $this->getFaker()->word(), 1);

        $checkResult = new FileStorageCheckResult(
            $orphanedPaths,
            [new PathSet($this->getFaker()->word(), FileStorageType::DOCUMENT, [$this->getFaker()->word() => $this->getFaker()->word()])],
        );

        $fileStorageChecker = Mockery::mock(FileStorageChecker::class);
        $fileStorageChecker->expects('check')
            ->andReturn($checkResult);

        $command = new FileStorageCheck($fileStorageChecker);

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
