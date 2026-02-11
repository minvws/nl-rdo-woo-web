<?php

declare(strict_types=1);

namespace Shared\Command\Index;

use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand(name: self::COMMAND_NAME, description: 'Deletes an ES index')]
class Delete extends Command
{
    public const string COMMAND_NAME = 'woopie:index:delete';

    public function __construct(protected ElasticIndexManager $indexService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'Name of the index'),
                new InputOption('force', '', InputOption::VALUE_NONE, 'Force'),
            ])
            ->setHelp('Deletes an ES index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        Assert::string($name);

        if (! $this->indexService->exists($name)) {
            $output->writeln("Index {$name} does not exist.");

            return self::FAILURE;
        }

        if ($input->getOption('force')) {
            $output->writeln("Deleting index {$name}.");
            $this->indexService->delete($name);
        }

        return self::SUCCESS;
    }
}
