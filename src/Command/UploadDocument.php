<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Dossier;
use App\Service\FileProcessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UploadDocument extends Command
{
    protected FileProcessService $fileProcessService;
    protected EntityManagerInterface $doctrine;

    public function __construct(FileProcessService $fileProcessService, EntityManagerInterface $doctrine)
    {
        parent::__construct();

        $this->fileProcessService = $fileProcessService;
        $this->doctrine = $doctrine;
    }

    protected function configure(): void
    {
        $this->setName('woopie:document:upload')
            ->setDescription('Triggers the processing of an uploaded document')
            ->setHelp('Triggers the processing of an uploaded document')
            ->setDefinition([
                new InputArgument('prefix', InputArgument::REQUIRED, 'The dossier document prefix'),
                new InputArgument('dossierNr', InputArgument::REQUIRED, 'The dossier number'),
                new InputArgument('path', InputArgument::REQUIRED, 'The path of the document'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prefix = strval($input->getArgument('prefix'));
        $dossierNr = strval($input->getArgument('dossierNr'));
        $dossier = $this->doctrine->getRepository(Dossier::class)->findOneBy(['documentPrefix' => $prefix, 'dossierNr' => $dossierNr]);
        if (! $dossier) {
            $output->writeln("<error>No dossier found for prefix '$prefix' and dossierNr '$dossierNr'</error>");

            return 1;
        }

        $path = strval($input->getArgument('path'));
        $file = new \SplFileInfo($path);

        $this->fileProcessService->processFile($file, $dossier, $path);

        return 0;
    }
}
