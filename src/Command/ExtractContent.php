<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Worker\Pdf\Tools\TesseractService;
use App\Service\Worker\Pdf\Tools\TikaService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ExtractContent extends Command
{
    public function __construct(
        private readonly TikaService $tika,
        private readonly TesseractService $tesseract,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:dev:extract-content')
            ->setDescription('Extracts content from a file using Tika and Tesseract')
            ->setHelp('Extracts content from a file using Tika and Tesseract')
            ->setDefinition([
                new InputArgument('file', InputArgument::REQUIRED, 'File to load'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filepath = strval($input->getArgument('file'));
        if (! file_exists($filepath)) {
            $output->writeln("File $filepath does not exist");

            return 1;
        }

        $io = new SymfonyStyle($input, $output);

        $tikaData = $this->tika->extract($filepath);

        $io->newLine(5);
        $io->section('Tika data');
        $io->text(json_encode($tikaData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $io->newLine(5);
        $io->section('Tika content');
        $io->text(trim($tikaData['X-TIKA:content']));

        $tesseractData = $this->tesseract->extract($filepath);

        $io->newLine(5);
        $io->section('Tesseract data');
        $io->text(trim($tesseractData));

        $io->newLine(5);

        return 0;
    }
}
