<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Process\Dossier;

use App\Domain\Ingest\Process\Dossier\DossierIngester;
use App\Domain\Ingest\Process\Dossier\IngestDossierCommand;
use App\Domain\Ingest\Process\Dossier\IngestDossierHandler;
use App\Domain\Ingest\Process\IngestProcessException;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class IngestDossierHandlerTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $doctrine;
    private DossierIngester&MockInterface $ingester;
    private IngestDossierHandler $handler;

    public function setUp(): void
    {
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->ingester = \Mockery::mock(DossierIngester::class);

        $this->handler = new IngestDossierHandler(
            $this->doctrine,
            $this->ingester,
        );
    }

    public function testInvoke(): void
    {
        $refresh = true;

        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);

        $this->doctrine->shouldReceive('getRepository->find')->with($dossierId)->andReturn($dossier);

        $this->ingester->expects('ingest')->with($dossier, $refresh);

        $this->doctrine->expects('flush');

        $this->handler->__invoke(new IngestDossierCommand($dossierId, $refresh));
    }

    public function testExceptionIsThrownWhenDossierCannotBeFound(): void
    {
        $refresh = false;

        $dossierId = Uuid::v6();

        $this->doctrine->shouldReceive('getRepository->find')->with($dossierId)->andReturnNull();

        $this->expectExceptionObject(IngestProcessException::forCannotFindDossier($dossierId));

        $this->handler->__invoke(new IngestDossierCommand($dossierId, $refresh));
    }
}
