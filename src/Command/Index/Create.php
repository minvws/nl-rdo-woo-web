<?php

declare(strict_types=1);

namespace Shared\Command\Index;

use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Shared\Domain\Search\Index\Rollover\MappingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand(name: self::COMMAND_NAME, description: 'Creates a new ES index')]
class Create extends Command
{
    public const string COMMAND_NAME = 'woopie:index:create';

    public function __construct(
        protected ElasticIndexManager $indexService,
        protected MappingService $mappingService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
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
        $name = $input->getArgument('name');
        Assert::string($name);

        if ($this->indexService->exists($name)) {
            $output->writeln("Index {$name} already exist.");

            return self::FAILURE;
        }

        $version = $input->getArgument('version');
        Assert::string($version);

        if ($version === 'latest') {
            $version = $this->mappingService->getLatestMappingVersion();
        } else {
            $version = (int) $version;
        }
        Assert::integer($version);

        $output->writeln("Creating index {$name} on version {$version}.");
        $this->indexService->create($name, $version);

        if ($input->getOption('read')) {
            $this->indexService->switch(ElasticConfig::READ_INDEX, '*', $name);
        }
        if ($input->getOption('write')) {
            $this->indexService->switch(ElasticConfig::WRITE_INDEX, '*', $name);
        }

        return self::SUCCESS;
    }
}
