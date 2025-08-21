<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AuditLogGenerateKey extends Command
{
    protected function configure(): void
    {
        $this->setName('woopie:auditlog:generate-keys')
            ->setDescription('Creates a new keypair for auditlog encryption');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        $keypair = sodium_crypto_box_keypair();
        $publicKey = sodium_crypto_box_publickey($keypair);
        $secretKey = sodium_crypto_box_secretkey($keypair);

        $output->writeln('Public key: <info>' . base64_encode($publicKey) . '</info>');
        $output->writeln('Secret key: <info>' . base64_encode($secretKey) . '</info>');

        return 0;
    }
}
