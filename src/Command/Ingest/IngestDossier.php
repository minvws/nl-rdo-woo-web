<?php

declare(strict_types=1);

namespace App\Command\Ingest;

use App\Domain\Ingest\Process\Dossier\IngestDossierCommand;
use App\Domain\Ingest\Process\IngestProcessOptions;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class IngestDossier extends Command
{
    public function __construct(
        private readonly AbstractDossierRepository $repository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:ingest:dossier')
            ->setDescription('Ingests a complete dossier into elasticsearch')
            ->setHelp('Ingests a complete dossier')
            ->setDefinition([
                new InputArgument('prefix', InputArgument::REQUIRED, 'The prefix of the dossier to ingest'),
                new InputArgument('dossierNr', InputArgument::REQUIRED, 'The dossiernr of the dossier to ingest'),
                new InputOption('force-refresh', 'f', InputOption::VALUE_NONE, 'Skip any caching'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix = strval($input->getArgument('prefix'));
        $dossierNr = strval($input->getArgument('dossierNr'));

        $options = new IngestProcessOptions();
        $options->setForceRefresh($input->getOption('force-refresh') == true);

        $dossier = $this->repository->findOneBy(['documentPrefix' => $prefix, 'dossierNr' => $dossierNr]);
        if (! $dossier) {
            $output->writeln('<error>Dossier with prefix ' . $prefix . ' and dossierNr ' . $dossierNr . ' not found</error>');

            return 1;
        }

        $this->messageBus->dispatch(
            new IngestDossierCommand(
                $dossier->getId(),
                boolval($input->getOption('force-refresh'))
            )
        );

        return 0;
    }
}
