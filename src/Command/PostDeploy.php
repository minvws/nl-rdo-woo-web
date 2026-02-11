<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Domain\Content\Page\ContentPageService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'woopie:post-deploy', description: 'Executes post deploy actions')]
class PostDeploy extends Command
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
        unset($input, $output);

        $this->contentPageService->createMissingPages();

        return self::SUCCESS;
    }
}
