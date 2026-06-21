<?php

declare(strict_types=1);

namespace Admin\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

#[AsCommand(name: 'woopie:post-deploy', description: 'Executes post deploy actions')]
class PostDeployAdmin extends Command
{
    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        return $this->createOpenApiSpecFiles($io);
    }

    private function createOpenApiSpecFiles(SymfonyStyle $io): int
    {
        $io->info('Creating OpenApi json and yaml files...');

        $command = $this->getApplication()?->find('api:openapi:export');
        Assert::notNull($command);

        $openApiArguments = [
            ['--output' => 'public/api/admin/v1/openapi.json'],
            ['--output' => 'public/api/admin/v1/openapi.yaml', '--yaml' => true],
        ];

        foreach ($openApiArguments as $args) {
            $returnCode = $command->run(new ArrayInput($args), $io);

            if ($returnCode !== self::SUCCESS) {
                return $returnCode;
            }
        }

        $io->comment('done creating OpenApi files');

        return self::SUCCESS;
    }
}
