<?php

declare(strict_types=1);

namespace App\Command\Index;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Alias extends Command
{
    protected ElasticIndexManager $indexService;

    public function __construct(ElasticIndexManager $indexService)
    {
        parent::__construct();

        $this->indexService = $indexService;
    }

    protected function configure(): void
    {
        $this->setName('woopie:index:alias')
            ->setDescription('Creates a new alias for an index')
            ->setDefinition([
                new InputArgument('name', InputArgument::REQUIRED, 'Name of the index'),
                new InputArgument('alias', InputArgument::REQUIRED, 'Name of the alias'),
            ])
            ->setHelp('Creates a new alias for the given index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = strval($input->getArgument('name'));
        $alias = strval($input->getArgument('alias'));

        if (! $this->indexService->exists($name)) {
            $output->writeln("Index {$name} does not exist.");

            return 1;
        }

        $output->writeln("Aliasing index {$name} to {$alias}.");
        $this->indexService->alias($name, $alias);

        return 0;
    }
}
