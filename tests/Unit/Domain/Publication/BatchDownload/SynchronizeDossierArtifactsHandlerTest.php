<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\BatchDownload\SynchronizeDossierArtifactsHandler;
use Shared\Domain\Publication\Dossier\Command\SynchronizeDossierArtifactsCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class SynchronizeDossierArtifactsHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private LoggerInterface&MockInterface $logger;
    private BatchDownloadService&MockInterface $batchDownloadService;
    private SynchronizeDossierArtifactsHandler $handler;

    protected function setUp(): void
    {
        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);

        $this->handler = new SynchronizeDossierArtifactsHandler(
            $this->dossierRepository,
            $this->logger,
            $this->batchDownloadService,
        );

        parent::setUp();
    }

    public function testInvokeReturnsEarlyWhenNoDossierFound(): void
    {
        $uuid = Uuid::v6();

        $this->dossierRepository->expects('find')->with($uuid)->andReturnNull();
        $this->logger->expects('warning');

        $this->batchDownloadService->expects('refresh')->never();

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }

    public function testInvokeReturnsEarlyWhenNonWooDecisionFound(): void
    {
        $uuid = Uuid::v6();
        $annualReport = Mockery::mock(AnnualReport::class);

        $this->dossierRepository->expects('find')->with($uuid)->andReturn($annualReport);
        $this->logger->expects('warning')->never();
        $this->batchDownloadService->expects('refresh')->never();

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }

    public function testInvokeCallsBatchDownloadServiceForWooDecision(): void
    {
        $uuid = Uuid::v6();
        $wooDecision = Mockery::mock(WooDecision::class);

        $this->dossierRepository->expects('find')->with($uuid)->andReturn($wooDecision);

        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static function (BatchDownloadScope $scope) use ($wooDecision) {
                self::assertEquals($wooDecision, $scope->wooDecision);

                return true;
            },
        ));

        $command = new SynchronizeDossierArtifactsCommand($uuid);
        $this->handler->__invoke($command);
    }
}
