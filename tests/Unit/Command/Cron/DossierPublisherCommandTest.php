<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command\Cron;

use App\Command\Cron\DossierPublisherCommand;
use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DossierPublisherCommandTest extends MockeryTestCase
{
    public function testExecute(): void
    {
        $unpublishableDossier = \Mockery::mock(WooDecision::class);
        $unpublishableDossier->shouldReceive('getDossierNr')->andReturn('D1');

        $publishableDossier = \Mockery::mock(WooDecision::class);
        $publishableDossier->shouldReceive('getDossierNr')->andReturn('D2');

        $publishablePreviewDossier = \Mockery::mock(WooDecision::class);
        $publishablePreviewDossier->shouldReceive('getDossierNr')->andReturn('D3');

        $repository = \Mockery::mock(DossierRepository::class);
        $repository->shouldReceive('findDossiersPendingPublication')->andReturn([
            $unpublishableDossier,
            $publishableDossier,
            $publishablePreviewDossier,
        ]);

        $publisher = \Mockery::mock(DossierPublisher::class);

        $publisher->expects('canPublish')->with($unpublishableDossier)->andReturnFalse();
        $publisher->expects('canPublishAsPreview')->with($unpublishableDossier)->andReturnFalse();

        $publisher->expects('canPublish')->with($publishableDossier)->andReturnTrue();
        $publisher->expects('publish')->with($publishableDossier);

        $publisher->expects('canPublish')->with($publishablePreviewDossier)->andReturnFalse();
        $publisher->expects('canPublishAsPreview')->with($publishablePreviewDossier)->andReturnTrue();
        $publisher->expects('publishAsPreview')->with($publishablePreviewDossier);

        $command = new DossierPublisherCommand(
            $repository,
            $publisher,
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();

        self::assertEquals(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('Publishing dossier: D2', $output);
        $this->assertStringContainsString('Publishing dossier as preview: D3', $output);
        $this->assertStringContainsString('Done', $output);
    }
}
