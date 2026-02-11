<?php

declare(strict_types=1);

namespace Shared\Command\Index;

use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

#[AsCommand(name: self::COMMAND_NAME, description: 'Creates a new alias for an index')]
class Alias extends Command
{
    public const string COMMAND_NAME = 'woopie:index:alias';

    public function __construct(protected ElasticIndexManager $indexService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'Name of the index'),
                new InputArgument('alias', InputArgument::REQUIRED, 'Name of the alias'),
            ])
            ->setHelp('Creates a new alias for the given index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        Assert::string($name);

        $alias = $input->getArgument('alias');
        Assert::string($alias);

        if (! $this->indexService->exists($name)) {
            $output->writeln("Index {$name} does not exist.");

            return self::FAILURE;
        }

        $output->writeln("Aliasing index {$name} to {$alias}.");
        $this->indexService->alias($name, $alias);

        return self::SUCCESS;
    }
}
