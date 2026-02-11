<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command;

use Mockery;
use Shared\Command\MoveOrphanedFiles;
use Shared\Domain\FileStorage\Checker\FileStorageChecker;
use Shared\Domain\FileStorage\Checker\FileStorageCheckResult;
use Shared\Domain\FileStorage\Checker\FileStorageType;
use Shared\Domain\FileStorage\Checker\OrphanedPaths;
use Shared\Domain\FileStorage\Checker\PathSet;
use Shared\Domain\FileStorage\OrphanedFileMover;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class MoveOrphanedFilesTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $orphanedPaths = new OrphanedPaths();
        $orphanedPaths->add(FileStorageType::DOCUMENT, $this->getFaker()->word(), 1);

        $pathSets = [
            new PathSet($this->getFaker()->word(), FileStorageType::DOCUMENT, [
                $this->getFaker()->word() => $this->getFaker()->word(),
            ]),
        ];

        $fileStorageCheckResult = new FileStorageCheckResult($orphanedPaths, $pathSets);

        $fileStorageChecker = Mockery::mock(FileStorageChecker::class);
        $fileStorageChecker->expects('check')
            ->andReturn($fileStorageCheckResult);

        $orphanedFileMover = Mockery::mock(OrphanedFileMover::class);
        $orphanedFileMover->expects('move');

        $command = new MoveOrphanedFiles($fileStorageChecker, $orphanedFileMover);

        $application = new Application();
        $application->add($command);

        $command = $application->find(MoveOrphanedFiles::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->setInputs([$this->getFaker()->word(), 'yes']);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteNoOrphanedFilesFound(): void
    {
        $fileStorageCheckResult = new FileStorageCheckResult(
            new OrphanedPaths(),
            [
                new PathSet($this->getFaker()->word(), FileStorageType::DOCUMENT, [
                    $this->getFaker()->word() => $this->getFaker()->word(),
                ]),
            ]
        );

        $fileStorageChecker = Mockery::mock(FileStorageChecker::class);
        $fileStorageChecker->expects('check')
            ->andReturn($fileStorageCheckResult);

        $orphanedFileMover = Mockery::mock(OrphanedFileMover::class);

        $command = new MoveOrphanedFiles($fileStorageChecker, $orphanedFileMover);

        $application = new Application();
        $application->add($command);

        $command = $application->find(MoveOrphanedFiles::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
