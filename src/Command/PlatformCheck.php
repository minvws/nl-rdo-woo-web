<?php

declare(strict_types=1);

namespace Shared\Command;

use Shared\Service\PlatformCheck\PlatformCheckerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(
    name: 'woopie:check:platform',
    description: 'Checks if the current platform is ready for running',
    aliases: ['woopie:check:production'],
    help: 'Sanity checks for the current platform',
)]
class PlatformCheck extends Command
{
    /**
     * @param iterable<PlatformCheckerInterface> $checkers
     */
    public function __construct(
        #[AutowireIterator('woo_platform.platform_checker')]
        private readonly iterable $checkers,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        unset($input);
        $returnCode = self::SUCCESS;

        $output->writeln('Woo Publication Platform sanity check status');
        $output->writeln('=========================================');
        $output->writeln('');

        foreach ($this->checkers as $checker) {
            foreach ($checker->getResults() as $result) {
                $output->write('<comment>📋 ' . $result->description . '</comment>: ');

                if (! $result->successful) {
                    $output->writeln('<error>💀 ' . $result->output . '</error>');
                    $returnCode = self::FAILURE;
                } elseif ($result->output !== '') {
                    $output->writeln('<info>👍 ' . $result->output . '</info>');
                }
            }
        }

        $output->writeln("\n");

        return $returnCode;
    }
}
