<?php

declare(strict_types=1);

namespace App\Command\Index;

use App\Service\Elastic\IndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Delete extends Command
{
    protected IndexService $indexService;

    public function __construct(IndexService $indexService)
    {
        parent::__construct();

        $this->indexService = $indexService;
    }

    protected function configure(): void
    {
        $this->setName('woopie:index:delete')
            ->setDescription('Deletes an ES index')
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'Name of the index'),
                new InputOption('force', '', InputOption::VALUE_NONE, 'Force'),
            ])
            ->setHelp('Deletes an ES index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = strval($input->getArgument('name'));

        if (! $this->indexService->exists($name)) {
            $output->writeln("Index {$name} does not exist.");

            return 1;
        }

        if ($input->getOption('force')) {
            $output->writeln("Deleting index {$name}.");
            $this->indexService->delete($name);
        }

        return 0;
    }
}
