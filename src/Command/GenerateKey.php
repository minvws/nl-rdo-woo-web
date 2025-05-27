<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Encryption\EncryptionService;
use ParagonIE\Halite\KeyFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKey extends Command
{
    public function __construct(protected EncryptionService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('generate:database-key')
            ->setDescription('Creates a new key to encrypt database entries')
            ->setHelp('Creates a new key to encrypt database entries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        try {
            $encKey = KeyFactory::generateEncryptionKey();
            $keyHex = KeyFactory::export($encKey)->getString();
        } catch (\Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return 1;
        }

        $output->writeln("Key: <info>$keyHex</info>");

        return 0;
    }
}
