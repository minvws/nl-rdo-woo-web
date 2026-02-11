<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileSetRemover;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

#[AsCommand(name: 'woopie:cron:clean-document-file-sets', description: 'Cleans up DocumentFileSet entities and related files')]
class CleanDocumentFileSets extends Command
{
    public function __construct(
        private readonly DocumentFileSetRemover $cleaner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $count = $this->cleaner->removeAllFinalSets();

        $output->writeln(sprintf('Cleaned up %d DocumentFileSets', $count));

        return self::SUCCESS;
    }
}
