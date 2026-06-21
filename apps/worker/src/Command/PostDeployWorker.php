<?php

declare(strict_types=1);

namespace Worker\Command;

use Shared\Domain\Content\Page\ContentPageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'woopie:post-deploy', description: 'Executes post deploy actions')]
class PostDeployWorker extends Command
{
    public function __construct(
        private readonly ContentPageService $contentPageService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Creating (missing) content pages...');
        $this->contentPageService->createMissingPages();
        $io->comment('done creating content pages');

        return self::SUCCESS;
    }
}
