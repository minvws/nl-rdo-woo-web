<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command
{
    protected OutputInterface $output;

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
        $this->checkExtension('pgsql', 'PGSQL');
        $this->checkExtension('intl', 'INTL');

        $this->checkWritablePath('var/documents');
        $this->checkWritablePath('var/thumbnails');

        $this->checkFile('/usr/bin/tesseract');
        $this->checkFile('/usr/bin/pdftk');
        $this->checkFile('/usr/bin/pdfseparate');
        $this->checkFile('/usr/bin/pdftoppm');

        $output->writeln("\n");

        return 0;
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

    protected function checkWritablePath(string $path): void
    {
        $this->output->write("<comment>📋 Checking if $path is writeable</comment>: ");
        $filename = $path . '/test-' . uniqid() . '.txt';
        $this->output->write($filename);
        if (! touch($filename)) {
            $this->error('not writable');

            return;
        }

        unlink($filename);
        $this->success();
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
    }

    protected function success(string $message = 'ok'): void
    {
        $this->output->writeln('<info>👍 ' . $message . '</info>');
    }
}
