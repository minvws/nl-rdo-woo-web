<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\SqlDump\NodeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Finder\Finder;

class SqlDump extends Command
{
    protected function configure(): void
    {
        $this->setName('woopie:sql:dump')
            ->setDescription('Dumps SQL from migrations')
            ->setHelp('Dumps SQL from migrations')
            ->setDefinition([
//                new InputOption('force-refresh', 'f', InputOption::VALUE_NONE, 'Skip any caching'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);
        unset($output);

        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromString('7.0'));
        $finder = new Finder();
        foreach ($finder->in(__DIR__ . '/../../migrations')->files()->name('*.php') as $file) {
            $sqlFilename = $file->getBasename('.php') . '.sql';
            $f = fopen($sqlFilename, 'w');
            if (! $f) {
                throw new \Exception("Could not open file $sqlFilename for writing");
            }
            $output = new StreamOutput($f);

            $output->writeln('-- Migration ' . $file->getBasename('.php'));
            $output->writeln('-- Generated on ' . date('Y-m-d H:i:s') . ' by bin/console woopie:sql:dump');
            $output->writeln('--');
            $output->writeln('');

            $ast = $parser->parse($file->getContents());
            if (! $ast) {
                throw new \Exception('Could not parse file ' . $file->getFilename());
            }

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NodeVisitor($output));
            $traverser->traverse($ast);

            $output->writeln("\n");
            fclose($f);
        }

        return 0;
    }
}
