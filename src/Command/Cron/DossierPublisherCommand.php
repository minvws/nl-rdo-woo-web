<?php

declare(strict_types=1);

namespace Shared\Command\Cron;

use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(name: 'woopie:cron:publisher', description: 'Publish dossiers when their publication date is reached')]
class DossierPublisherCommand extends Command
{
    public function __construct(
        private readonly DossierRepository $repository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $dossiers = $this->repository->findDossiersPendingPublication();
        foreach ($dossiers as $dossier) {
            $this->messageBus->dispatch(new UpdateDossierPublicationCommand($dossier));
        }

        $output->writeln('<info>Done');

        return self::SUCCESS;
    }
}
