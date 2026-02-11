<?php

declare(strict_types=1);

namespace Shared\Command;

use ParagonIE\Halite\KeyFactory;
use Shared\Service\Encryption\EncryptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'generate:database-key', description: 'Creates a new key to encrypt database entries')]
class GenerateKey extends Command
{
    public function __construct(protected EncryptionService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Creates a new key to encrypt database entries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        try {
            $encKey = KeyFactory::generateEncryptionKey();
            $keyHex = KeyFactory::export($encKey)->getString();
        } catch (Throwable $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }

        $output->writeln("Key: <info>$keyHex</info>");

        return self::SUCCESS;
    }
}
