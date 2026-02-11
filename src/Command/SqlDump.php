<?php

declare(strict_types=1);

namespace Shared\Command;

use Exception;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Shared\Service\SqlDump\NodeVisitor;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Finder\Finder;

use function date;
use function fclose;
use function file_exists;
use function fopen;
use function ltrim;
use function sprintf;
use function strtolower;

/**
 * @codeCoverageIgnore This command should get refactored and get full coverage then.
 */
#[AsCommand(name: 'woopie:sql:dump', description: 'Dumps SQL from migrations')]
class SqlDump extends Command
{
    public const SQL_MIGRATION_PATH = __DIR__ . '/../../migrations/sql';

    protected function configure(): void
    {
        $this
            ->setHelp('Dumps SQL from migrations')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite existing sql files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($output);

        $parser = (new ParserFactory())->createForVersion(PhpVersion::fromString('7.0'));
        $finder = new Finder();
        foreach ($finder->in(__DIR__ . '/../../migrations')->files()->name('*.php') as $file) {
            $sqlFilename = $this->getSqlFilename($file);
            $sqlFullpath = sprintf('%s/%s', self::SQL_MIGRATION_PATH, $sqlFilename);

            if (file_exists($sqlFullpath) && ! $input->getOption('force')) {
                continue;
            }

            $f = fopen($sqlFullpath, 'w');
            if (! $f) {
                throw new Exception("Could not open file $sqlFilename for writing");
            }
            $output = new StreamOutput($f);

            $output->writeln('-- Migration ' . $file->getBasename('.php'));
            $output->writeln('-- Generated on ' . date('Y-m-d H:i:s') . ' by bin/console woopie:sql:dump');
            $output->writeln('--');
            $output->writeln('');

            $ast = $parser->parse($file->getContents());
            if (! $ast) {
                throw new Exception('Could not parse file ' . $file->getFilename());
            }

            $traverser = new NodeTraverser();
            $traverser->addVisitor(new NodeVisitor($output));
            $traverser->traverse($ast);

            $output->writeln("\n");
            fclose($f);
        }

        return self::SUCCESS;
    }

    private function getSqlFilename(SplFileInfo $file): string
    {
        $basename = ltrim(strtolower($file->getBasename('.php')), 'version');

        return sprintf('%s.sql', $basename);
    }
}
