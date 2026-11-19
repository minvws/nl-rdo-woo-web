<?php

declare(strict_types=1);

namespace Shared\Command\Index;

use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand(name: self::COMMAND_NAME, description: 'Creates a new ES index', help: 'Creates a new ES index')]
class Create extends Command
{
    public const string COMMAND_NAME = 'woopie:index:create';

    public function __construct(
        protected ElasticIndexManager $indexService,
        private readonly ElasticConfig $elasticConfig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'Name of the index'),
                new InputOption('read', 'r', InputOption::VALUE_NONE, 'Set read alias'),
                new InputOption('write', 'w', InputOption::VALUE_NONE, 'Set write alias'),
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        Assert::string($name);

        if ($this->indexService->exists($name)) {
            $output->writeln("Index {$name} already exist.");

            return self::FAILURE;
        }

        $output->writeln("Creating index {$name} with the current mapping.");
        $this->indexService->create($name);

        if ($input->getOption('read')) {
            $this->indexService->switch($this->elasticConfig->readIndex, '*', $name);
        }
        if ($input->getOption('write')) {
            $this->indexService->switch($this->elasticConfig->writeIndex, '*', $name);
        }

        return self::SUCCESS;
    }
}
