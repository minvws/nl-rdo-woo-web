<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index\Dossier;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\Index\Dossier\DossierIndexer;
use Shared\Domain\Search\Index\Dossier\IndexDossierCommand;
use Shared\Domain\Search\Index\Dossier\IndexDossierHandler;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class IndexDossierHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private DossierIndexer&MockInterface $dossierIndexer;
    private LoggerInterface&MockInterface $logger;
    private IndexDossierHandler $handler;

    protected function setUp(): void
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
