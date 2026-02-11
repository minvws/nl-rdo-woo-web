<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use Shared\Domain\Upload\UploadCleaner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'woopie:cron:clean-uploads', description: 'Cleans up Upload entities and related files')]
class CleanUploads extends Command
{
    public function __construct(
        private readonly UploadCleaner $cleaner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $this->cleaner->cleanup();

        $output->writeln('Done cleaning uploads!');

        return self::SUCCESS;
    }
}
