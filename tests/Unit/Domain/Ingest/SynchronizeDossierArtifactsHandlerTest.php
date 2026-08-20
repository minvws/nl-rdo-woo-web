<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Process\Dossier\DossierIngester;
use Shared\Domain\Ingest\SynchronizeDossierArtifactsHandler;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class SynchronizeDossierArtifactsHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private LoggerInterface&MockInterface $logger;
    private DossierIngester&MockInterface $dossierIngester;
    private SynchronizeDossierArtifactsHandler $handler;

    protected function setUp(): void
    {
        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->dossierIngester = Mockery::mock(DossierIngester::class);

        $this->handler = new SynchronizeDossierArtifactsHandler(
            $this->dossierRepository,
            $this->logger,
            $this->dossierIngester,
        );
    }

    public function testInvokeReturnsEarlyWhenNoDossierFound(): void
    {
        $uuid = Uuid::v6();

        $this->dossierRepository->expects('find')->with($uuid)->andReturnNull();
        $this->logger->expects('warning');

        $this->dossierIngester->expects('ingest')->never();

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }

    public function testInvokeCallsInventoryServiceForWooDecision(): void
    {
        $uuid = Uuid::v6();
        $dossier = Mockery::mock(AbstractDossier::class);

        $this->dossierRepository->expects('find')->with($uuid)->andReturn($dossier);
        $this->dossierIngester->expects('ingest')->with($dossier, true);

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }
}
