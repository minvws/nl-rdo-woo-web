<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Command\Cron;

use Mockery;
use Shared\Command\Cron\DossierPublisherCommand;
use Shared\Domain\Publication\Dossier\DossierPublisher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DossierPublisherCommandTest extends UnitTestCase
{
    public function testExecute(): void
    {
        $unpublishableDossier = Mockery::mock(WooDecision::class);
        $unpublishableDossier->expects('getDossierNr')
            ->never();

        $publishableDossier = Mockery::mock(WooDecision::class);
        $publishableDossier->expects('getDossierNr')
            ->andReturn('D2');

        $publishablePreviewDossier = Mockery::mock(WooDecision::class);
        $publishablePreviewDossier->expects('getDossierNr')
            ->andReturn('D3');

        $repository = Mockery::mock(DossierRepository::class);
        $repository->expects('findDossiersPendingPublication')
            ->andReturn([
                $unpublishableDossier,
                $publishableDossier,
                $publishablePreviewDossier,
            ]);

        $publisher = Mockery::mock(DossierPublisher::class);

        $publisher->expects('canPublish')
            ->with($unpublishableDossier)
            ->andReturnFalse();
        $publisher->expects('canPublishAsPreview')
            ->with($unpublishableDossier)
            ->andReturnFalse();

        $publisher->expects('canPublish')
            ->with($publishableDossier)
            ->andReturnTrue();
        $publisher->expects('publish')
            ->with($publishableDossier);

        $publisher->expects('canPublish')
            ->with($publishablePreviewDossier)
            ->andReturnFalse();
        $publisher->expects('canPublishAsPreview')
            ->with($publishablePreviewDossier)
            ->andReturnTrue();
        $publisher->expects('publishAsPreview')
            ->with($publishablePreviewDossier);

        $command = new DossierPublisherCommand(
            $repository,
            $publisher,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals($command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Publishing dossier: D2', $output);
        $this->assertStringContainsString('Publishing dossier as preview: D3', $output);
        $this->assertStringContainsString('Done', $output);
    }
}
