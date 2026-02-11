<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Service\RevokedUrlService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'woo:export-revoked-urls', description: 'Exports urls for revoked documents (withdrawn or suspended)')]
class ExportRevokedUrls extends Command
{
    public function __construct(
        private readonly RevokedUrlService $revokedUrlService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);

        foreach ($this->revokedUrlService->getUrls() as $url) {
            $output->writeln($url);
        }

        return self::SUCCESS;
    }
}
