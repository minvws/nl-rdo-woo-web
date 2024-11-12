<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Dossier;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\Dossier\DossierIndexer;
use App\Domain\Search\Index\Dossier\IndexDossierCommand;
use App\Domain\Search\Index\Dossier\IndexDossierHandler;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class IndexDossierHandlerTest extends MockeryTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private DossierIndexer&MockInterface $dossierIndexer;
    private LoggerInterface&MockInterface $logger;
    private IndexDossierHandler $handler;

    public function setUp(): void
    {
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->dossierIndexer = \Mockery::mock(DossierIndexer::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);

        $this->handler = new IndexDossierHandler(
            $this->dossierRepository,
            $this->dossierIndexer,
            $this->logger,
        );
    }

    public function testInvoke(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);

        $this->dossierRepository->shouldReceive('find')->with($dossierId)->andReturn($dossier);

        $this->dossierIndexer->expects('index')->with($dossier, true);

        $this->handler->__invoke(new IndexDossierCommand($dossierId));
    }

    public function testWarningIsLoggedWhenDossierCannotBeFound(): void
    {
        $dossierId = Uuid::v6();

        $this->dossierRepository->shouldReceive('find')->with($dossierId)->andReturnNull();

        $this->logger->expects('warning');

        $this->handler->__invoke(new IndexDossierCommand($dossierId));
    }

    public function testErrorIsLoggedWhenExceptionOccurs(): void
    {
        $dossierId = Uuid::v6();

        $this->dossierRepository->shouldReceive('find')->with($dossierId)->andThrow(new \RuntimeException('oops'));

        $this->logger->expects('error');

        $this->handler->__invoke(new IndexDossierCommand($dossierId));
    }
}
