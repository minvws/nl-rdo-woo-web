<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\GenerateArchiveMessage;
use App\Service\Logging\LoggingHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Uid\Uuid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GenerateBatch extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggingHelper $loggingHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:generate:batch')
            ->setDescription('Create new batch')
            ->setHelp('Create new batch - send batch id')
            ->setDefinition([
                new InputOption('batchId', 'b', InputOption::VALUE_REQUIRED, 'batch ID'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->loggingHelper->disableAll();

        $stopwatch = new Stopwatch();
        $stopwatch->start('generate-batch');

        $batchId = strval($input->getOption('batchId'));

        // Dispatch message to generate archive
        $this->messageBus->dispatch(new GenerateArchiveMessage(Uuid::fromString($batchId)));

        $stopwatch->stop('generate-batch');
        $output->writeln($stopwatch->getEvent('generate-batch')->__toString());

        $this->loggingHelper->restoreAll();

        return 0;
    }
}
