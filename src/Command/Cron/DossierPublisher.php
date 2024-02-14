<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Entity\Dossier;
use App\Enum\PublicationStatus;
use App\Service\DossierService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DossierPublisher extends Command
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected DossierService $dossierService;

    public function __construct(EntityManagerInterface $doctrine, DossierService $dossierService, LoggerInterface $logger)
    {
        parent::__construct();

        $this->doctrine = $doctrine;
        $this->dossierService = $dossierService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setName('woopie:cron:publisher')
            ->setDescription('Publish dossiers when their publication date is reached')
            ->setHelp('Publish dossiers when their publication date is reached')
            ->addArgument('date', InputArgument::OPTIONAL, 'Date to publish dossiers for', 'now midnight')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = new \DateTimeImmutable(strval($input->getArgument('date')));

        $dossiers = $this->doctrine->getRepository(Dossier::class)->findPendingPreviewDossiers($date);
        foreach ($dossiers as $dossier) {
            $output->writeln('Moving dossier to status PREVIEW: ' . $dossier->getDossierNr());
            $this->dossierService->changeState($dossier, PublicationStatus::PREVIEW);
        }
        $dossiers = $this->doctrine->getRepository(Dossier::class)->findPendingPublishDossiers($date);
        foreach ($dossiers as $dossier) {
            $output->writeln('Moving dossier to status PUBLISHED: ' . $dossier->getDossierNr());
            $this->dossierService->changeState($dossier, PublicationStatus::PUBLISHED);
        }

        return 0;
    }
}
