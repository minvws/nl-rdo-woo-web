<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Domain\Upload\UploadCleaner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUploads extends Command
{
    public function __construct(
        private readonly UploadCleaner $cleaner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:cron:clean-uploads')
            ->setDescription('Cleans up Upload entities and related files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $this->cleaner->cleanup();

        $output->writeln('Done cleaning uploads!');

        return 0;
    }
}
