<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Domain\FileStorage\Checker\FileStorageChecker;
use Shared\Domain\FileStorage\Checker\FileStorageCheckResult;
use Shared\Service\Utils\Utils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function round;
use function sprintf;
use function strtoupper;

#[AsCommand(name: 'woopie:check:storage', description: 'Checks if files in storage can be matched to the database')]
class FileStorageCheck extends Command
{
    public function __construct(
        private readonly FileStorageChecker $checker,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $result = $this->checker->check();

        $this->listTotals($output, $result);

        $this->listMissingFiles($output, $result);

        if ($output->isVerbose()) {
            $this->listOrphanedFiles($result, $output);
        }

        return self::SUCCESS;
    }

    private function listTotals(OutputInterface $output, FileStorageCheckResult $result): void
    {
        $output->writeln("\n");
        $table = new Table($output);
        $table->setHeaders(['Type', 'Matched files', 'Total size', 'Usage', 'Missing files']);

        $totalCount = $result->orphanedPaths->totalCount;
        $totalSize = $result->orphanedPaths->totalSize;
        foreach ($result->pathSets as $pathSet) {
            $totalCount += $pathSet->totalCount;
            $totalSize += $pathSet->totalSize;
        }

        foreach ($result->pathSets as $pathSet) {
            $table->addRow([
                $pathSet->name,
                Utils::number($pathSet->totalCount),
                Utils::size($pathSet->totalSize),
                round($pathSet->totalSize / $totalSize * 100, 2) . '%',
                count($pathSet->getRemainingPaths()),
            ]);
        }

        $table->addRow(new TableSeparator());
        $table->addRow([
            'ORPHANED FILES',
            Utils::number($result->orphanedPaths->totalCount),
            Utils::size($result->orphanedPaths->totalSize),
            round($result->orphanedPaths->totalSize / $totalSize * 100, 2) . '%',
            '',
        ]);

        $table->addRow(new TableSeparator());
        $table->addRow([
            'TOTALS',
            Utils::number($totalCount),
            Utils::size($totalSize),
            '',
            '',
        ]);

        $table->render();
        $output->writeln("\n");
    }

    private function listOrphanedFiles(FileStorageCheckResult $result, OutputInterface $output): void
    {
        foreach ($result->orphanedPaths->paths as $storageType => $orphanedPaths) {
            $output->writeln("\n");
            $output->writeln(sprintf('Orphaned files in %s bucket:', strtoupper($storageType)));
            foreach ($orphanedPaths as $path) {
                $output->writeln($path);
            }
        }
    }

    private function listMissingFiles(OutputInterface $output, FileStorageCheckResult $result): void
    {
        $missingFiles = [];
        foreach ($result->pathSets as $pathSet) {
            foreach ($pathSet->getRemainingPaths() as $path => $uuid) {
                $missingFiles[] = [
                    'entity' => $pathSet->name,
                    'path' => $path,
                    'id' => $uuid,
                ];
            }
        }

        if (count($missingFiles) === 0) {
            return;
        }

        $output->writeln('Missing files:');
        $table = new Table($output);
        $table->setHeaders(['Type', 'Id', 'ExpectedPath']);
        foreach ($missingFiles as $missingFile) {
            $table->addRow([
                $missingFile['entity'],
                $missingFile['id'],
                $missingFile['path'],
            ]);
        }
        $table->render();
        $output->writeln("\n");
    }
}
