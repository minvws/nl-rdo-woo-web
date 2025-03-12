<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\DocumentFileSetRemover;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanDocumentFileSets extends Command
{
    public function __construct(
        private readonly DocumentFileSetRemover $cleaner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:cron:clean-document-file-sets')
            ->setDescription('Cleans up DocumentFileSet entities and relates files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $count = $this->cleaner->removeAllFinalSets();

        $output->writeln(sprintf('Cleaned up %d DocumentFileSets', $count));

        return 0;
    }
}
