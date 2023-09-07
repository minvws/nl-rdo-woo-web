<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\FixtureService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixture extends Command
{
    protected FixtureService $fixtureService;

    public function __construct(FixtureService $fixtureService)
    {
        parent::__construct();

        $this->fixtureService = $fixtureService;
    }

    protected function configure(): void
    {
        $this->setName('woopie:load:fixture')
            ->setDescription('Loads dossiers and documents from given fixture file into the system')
            ->setHelp('Loads dossiers and documents from given fixture file into the system')
            ->setDefinition([
                new InputArgument('file', InputArgument::REQUIRED, 'Fixture json file to load'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = strval($input->getArgument('file'));
        if (! file_exists($file)) {
            $output->writeln("File $file does not exist");

            return 1;
        }
        $path = dirname($file);

        $fileData = file_get_contents($file);
        if (! $fileData) {
            $output->writeln("File $file is empty or not readable");

            return 1;
        }

        $data = json_decode($fileData, true);
        if (! $data) {
            $output->writeln("File $file is not valid JSON");

            return 1;
        }

        /** @var array{name: string, dossiers: mixed[]} $data */
        $output->writeln("📝 Creating fixture:  {$data['name']}...");

        try {
            foreach ($data['dossiers'] as $dossier) {
                /** @var string[] $dossier */
                $output->writeln('📁 Creating dossier: ' . $dossier['title'] . '...');
                $this->fixtureService->create($dossier, $path);
            }
        } catch (\Exception $exception) {
            $output->writeln("⚠️ Error loading fixture: {$exception->getMessage()}");
            $output->writeln('Further processing is halted, data might be in an invalid state!');

            return 1;
        }

        $output->writeln('👍 Fixture loaded');

        return 0;
    }
}
