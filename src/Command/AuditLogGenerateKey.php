<?php

declare(strict_types=1);

namespace Shared\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function base64_encode;
use function sodium_crypto_box_keypair;
use function sodium_crypto_box_publickey;
use function sodium_crypto_box_secretkey;

#[AsCommand(name: 'woopie:auditlog:generate-keys', description: 'Creates a new keypair for auditlog encryption')]
class AuditLogGenerateKey extends Command
{
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $keypair = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($keypair);
        $secretKey = sodium_crypto_box_secretkey($keypair);

        $output->writeln('Public key: <info>' . base64_encode($publicKey) . '</info>');
        $output->writeln('Secret key: <info>' . base64_encode($secretKey) . '</info>');

        return self::SUCCESS;
    }
}
