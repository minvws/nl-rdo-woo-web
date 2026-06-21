<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Command;

use Admin\Command\PostDeployAdmin;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Webmozart\Assert\Assert;

class PostDeployAdminTest extends UnitTestCase
{
    public function testOpenApiSpecFilesAreCreated(): void
    {
        $exportedOutputs = [];

        $application = new Application();
        $application->addCommand(new PostDeployAdmin());
        $application->addCommand(new class($exportedOutputs) extends Command {
            /**
             * @param array<int, string> $exportedOutputs
             *
             * @phpstan-ignore property.onlyWritten
             */
            public function __construct(private array &$exportedOutputs)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->setName('api:openapi:export');
                $this->addOption('output', 'o', InputOption::VALUE_REQUIRED);
                $this->addOption('yaml', 'y', InputOption::VALUE_NONE);
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                $value = $input->getOption('output');
                Assert::string($value);
                $this->exportedOutputs[] = $value;

                return self::SUCCESS;
            }
        });

        $commandTester = new CommandTester($application->find('woopie:post-deploy'));
        $commandTester->execute([]);

        self::assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
        self::assertEquals(
            ['public/api/admin/v1/openapi.json', 'public/api/admin/v1/openapi.yaml'],
            $exportedOutputs,
        );
    }
}
