<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PlatformCheck\PlatformCheckerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlatformCheck extends Command
{
    /**
     * @param iterable<PlatformCheckerInterface> $checkers
     */
    public function __construct(private readonly iterable $checkers)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('woopie:check:platform')
            ->setAliases(['woopie:check:production'])
            ->setDescription('Checks if the current platform is ready for running')
            ->setHelp('Sanity checks for the current platform')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);
        $returnCode = 0;

        $output->writeln('Woo platform sanity check status');
        $output->writeln('=========================================');
        $output->writeln('');

        foreach ($this->checkers as $checker) {
            foreach ($checker->getResults() as $result) {
                $output->write('<comment>📋 ' . $result->description . '</comment>: ');

                if (! $result->successful) {
                    $output->writeln('<error>💀 ' . $result->output . '</error>');
                    $returnCode = 1;
                } elseif (! empty($result->output)) {
                    $output->writeln('<info>👍 ' . $result->output . '</info>');
                }
            }
        }

        $output->writeln("\n");

        return $returnCode;
    }
}
