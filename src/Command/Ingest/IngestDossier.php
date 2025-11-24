<?php

declare(strict_types=1);

namespace Shared\Command\Ingest;

use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IngestDossier extends Command
{
    public function __construct(
        private readonly DossierRepository $repository,
        private readonly IngestDispatcher $ingestDispatcher,
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
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix = strval($input->getArgument('prefix'));
        $dossierNr = strval($input->getArgument('dossierNr'));

        $dossier = $this->repository->findOneBy(['documentPrefix' => $prefix, 'dossierNr' => $dossierNr]);
        if (! $dossier) {
            $output->writeln('<error>Dossier with prefix ' . $prefix . ' and dossierNr ' . $dossierNr . ' not found</error>');

            return 1;
        }

        $this->ingestDispatcher->dispatchIngestDossierCommand(
            $dossier,
            boolval($input->getOption('force-refresh')),
        );

        return 0;
    }
}
