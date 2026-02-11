<?php

declare(strict_types=1);

namespace Shared\Command\Ingest;

use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function boolval;
use function sprintf;

#[AsCommand(name: self::COMMAND_NAME, description: 'Ingests a complete dossier into elasticsearch')]
class IngestDossier extends Command
{
    public const string COMMAND_NAME = 'woopie:ingest:dossier';

    public function __construct(
        private readonly DossierRepository $repository,
        private readonly IngestDispatcher $ingestDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Ingests a complete dossier')
            ->setDefinition([
                new InputArgument('prefix', InputArgument::REQUIRED, 'The prefix of the dossier to ingest'),
                new InputArgument('dossierNr', InputArgument::REQUIRED, 'The dossiernr of the dossier to ingest'),
                new InputOption('force-refresh', 'f', InputOption::VALUE_NONE, 'Skip any caching'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix = $input->getArgument('prefix');
        Assert::string($prefix);

        $dossierNr = $input->getArgument('dossierNr');
        Assert::string($dossierNr);

        $dossier = $this->repository->findOneBy(['documentPrefix' => $prefix, 'dossierNr' => $dossierNr]);
        if (! $dossier) {
            $output->writeln(sprintf('<error>Dossier with prefix %s and dossierNr %s not found</error>', $prefix, $dossierNr));

            return self::FAILURE;
        }

        $this->ingestDispatcher->dispatchIngestDossierCommand(
            $dossier,
            boolval($input->getOption('force-refresh')),
        );

        return self::SUCCESS;
    }
}
