<?php

declare(strict_types=1);

namespace App\Command\Index;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use App\Domain\Search\Index\Rollover\MappingService;
use App\ElasticConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command
{
    public function __construct(
        protected ElasticIndexManager $indexService,
        protected MappingService $mappingService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:index:create')
            ->setDescription('Creates a new ES index')
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'Name of the index'),
                new InputArgument('version', InputArgument::REQUIRED, 'Mapping version to use or "latest" for the latest version'),
                new InputOption('read', 'r', InputOption::VALUE_NONE, 'Set read alias'),
                new InputOption('write', 'w', InputOption::VALUE_NONE, 'Set write alias'),
            ])
            ->setHelp('Creates a new ES index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = strval($input->getArgument('name'));
        if ($this->indexService->exists($name)) {
            $output->writeln("Index {$name} already exist.");

            return 1;
        }

        $version = intval($input->getArgument('version'));
        if ($input->getArgument('version') == 'latest') {
            $version = $this->mappingService->getLatestMappingVersion();
        }

        $output->writeln("Creating index {$name} on version {$version}.");
        $this->indexService->create($name, $version);

        if ($input->getOption('read')) {
            $this->indexService->switch(ElasticConfig::READ_INDEX, '*', $name);
        }
        if ($input->getOption('write')) {
            $this->indexService->switch(ElasticConfig::WRITE_INDEX, '*', $name);
        }

        return 0;
    }
}
