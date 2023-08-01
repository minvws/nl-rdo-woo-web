<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Dossier;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UploadDocument extends Command
{
    protected DocumentService $documentService;
    protected EntityManagerInterface $doctrine;

    public function __construct(DocumentService $documentService, EntityManagerInterface $doctrine)
    {
        parent::__construct();

        $this->documentService = $documentService;
        $this->doctrine = $doctrine;
    }

    protected function configure(): void
    {
        $this->setName('woopie:document:upload')
            ->setDescription('Triggers the processing of an uploaded document')
            ->setHelp('Triggers the processing of an uploaded document')
            ->setDefinition([
                new InputArgument('dossierNr', InputArgument::REQUIRED, 'The dossier number'),
                new InputArgument('path', InputArgument::REQUIRED, 'The path of the document'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dossierNr = strval($input->getArgument('dossierNr'));
        $dossier = $this->doctrine->getRepository(Dossier::class)->findOneBy(['dossierNr' => $dossierNr]);
        if (! $dossier) {
            $output->writeln("<error>No dossier found for dossierNr $dossierNr</error>");

            return 1;
        }

        $path = strval($input->getArgument('path'));
        $file = new \SplFileInfo($path);

        $this->documentService->processDocument($file, $dossier, $path);

        return 0;
    }
}
