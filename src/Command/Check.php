<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Storage\DocumentStorageService;
use App\Service\Storage\ThumbnailStorageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command
{
    protected OutputInterface $output;

    protected DocumentStorageService $docStoreService;
    protected ThumbnailStorageService $thumbStoreService;

    protected int $returnCode = 0;

    public function __construct(
        DocumentStorageService $docStoreService,
        ThumbnailStorageService $thumbStoreService,
    ) {
        parent::__construct();

        $this->docStoreService = $docStoreService;
        $this->thumbStoreService = $thumbStoreService;
    }

    protected function configure(): void
    {
        $this->setName('woopie:check:production')
            ->setDescription('Checks if the current platform is ready for running')
            ->setHelp('Sanity checks for the current platform')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);
        $this->output = $output;

        $output->writeln('WooPIE sanity check for production status');
        $output->writeln('=========================================');
        $output->writeln('');

        $this->checkExtension('amqp', 'AMQP');
        $this->checkExtension('json', 'JSON');
        $this->checkExtension('pdo_pgsql', 'PDO_PGSQL');
        $this->checkExtension('intl', 'INTL');
        $this->checkExtension('zip', 'ZIP');

        $this->checkAlive('document store', $this->docStoreService->isAlive());
        $this->checkAlive('thumbnail store', $this->thumbStoreService->isAlive());

        $this->checkFile('/usr/bin/tesseract');
        $this->checkFile('/usr/bin/pdftk');
        $this->checkFile('/usr/bin/pdfseparate');
        $this->checkFile('/usr/bin/pdftoppm');
        $this->checkFile('/usr/bin/7za');
        $this->checkFile('/usr/bin/xlsx2csv');

        $output->writeln("\n");

        return $this->returnCode;
    }

    protected function checkFile(string $path): void
    {
        $this->output->write("<comment>📋 Checking if {$path} is found</comment>: ");
        if (! file_exists($path)) {
            $this->error('not found');

            return;
        }

        $this->success();
    }

    protected function checkAlive(string $system, bool $alive): void
    {
        $this->output->write("<comment>📋 Checking if $system is alive</comment>: ");
        if ($alive) {
            $this->success();

            return;
        }

        $this->error('not writable');
    }

    protected function checkExtension(string $extension, string $name): void
    {
        $this->output->write("<comment>📋 Checking if PHP {$name} extension is loaded</comment>: ");
        if (! extension_loaded($extension)) {
            $this->error('not loaded');

            return;
        }

        $this->success();
    }

    protected function error(string $message): void
    {
        $this->output->writeln('<error>💀 ' . $message . '</error>');

        $this->returnCode = 1;
    }

    protected function success(string $message = 'ok'): void
    {
        $this->output->writeln('<info>👍 ' . $message . '</info>');
    }
}
