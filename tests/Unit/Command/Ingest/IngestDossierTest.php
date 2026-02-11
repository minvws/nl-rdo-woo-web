<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Ingest;

use Mockery;
use Shared\Command\Ingest\IngestDossier;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class IngestDossierTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $prefix = $this->getFaker()->word();
        $dossierNr = $this->getFaker()->word();
        $forceRefresh = $this->getFaker()->boolean();

        $dossier = new WooDecision();

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneBy')
            ->with([
                'documentPrefix' => $prefix,
                'dossierNr' => $dossierNr,
            ])
            ->andReturn($dossier);

        $ingestDispatcher = Mockery::mock(IngestDispatcher::class);
        $ingestDispatcher->expects('dispatchIngestDossierCommand')
            ->with($dossier, $forceRefresh);

        $application = new Application();
        $application->add(new IngestDossier($dossierRepository, $ingestDispatcher));

        $command = $application->find(IngestDossier::COMMAND_NAME);
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'prefix' => $prefix,
            'dossierNr' => $dossierNr,
            '--force-refresh' => $forceRefresh,
        ]);

        self::assertEquals(IngestDossier::SUCCESS, $commandTester->getStatusCode());
    }
}
