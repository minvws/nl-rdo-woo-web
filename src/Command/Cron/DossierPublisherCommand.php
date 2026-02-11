<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use RuntimeException;
use Shared\Domain\Publication\Dossier\DossierPublisher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

#[AsCommand(name: 'woopie:cron:publisher', description: 'Publish dossiers when their publication date is reached')]
class DossierPublisherCommand extends Command
{
    public function __construct(
        private readonly DossierRepository $repository,
        private readonly DossierPublisher $publisher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $dossiers = $this->repository->findDossiersPendingPublication();
        foreach ($dossiers as $dossier) {
            try {
                if ($this->publisher->canPublish($dossier)) {
                    $this->publisher->publish($dossier);
                    $output->writeln('<info>Publishing dossier: ' . $dossier->getDossierNr());

                    continue;
                }

                if ($this->publisher->canPublishAsPreview($dossier)) {
                    $this->publisher->publishAsPreview($dossier);
                    $output->writeln('<info>Publishing dossier as preview: ' . $dossier->getDossierNr());
                }
            } catch (RuntimeException $exception) {
                $output->writeln(sprintf(
                    '<error>Skipping dossier %s because of an error: %s',
                    $dossier->getDossierNr(),
                    $exception->getMessage(),
                ));
            }
        }

        $output->writeln('<info>Done');

        return self::SUCCESS;
    }
}
