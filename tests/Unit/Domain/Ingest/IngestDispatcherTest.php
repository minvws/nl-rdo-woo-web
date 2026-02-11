<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest;

use Doctrine\ORM\Query;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Ingest\Process\Dossier\IngestAllDossiersCommand;
use Shared\Domain\Ingest\Process\Dossier\IngestDossierCommand;
use Shared\Domain\Ingest\Process\MetadataOnly\IngestMetadataOnlyCommand;
use Shared\Domain\Ingest\Process\Pdf\IngestPdfCommand;
use Shared\Domain\Ingest\Process\PdfPage\IngestPdfPageCommand;
use Shared\Domain\Ingest\Process\TikaOnly\IngestTikaOnlyCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class IngestDispatcherTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private MessageBusInterface&MockInterface $messageBus;
    private IngestDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->messageBus = Mockery::mock(MessageBusInterface::class);

        $this->dispatcher = new IngestDispatcher(
            $this->dossierRepository,
            $this->messageBus,
        );
    }

    public function testDispatchIngestDossierCommand(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getId')->andReturn($dossierId = Uuid::v6());

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (IngestDossierCommand $command) use ($dossierId) {
                self::assertEquals($dossierId, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestDossierCommand($dossier);
    }

    public function testDispatchIngestAllDossiersCommand(): void
    {
        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::type(IngestAllDossiersCommand::class))
            ->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestAllDossiersCommand();
    }

    public function testDispatchIngestDossierCommandForAllDossiers(): void
    {
        $dossierAId = Uuid::v6();
        $dossierBId = Uuid::v6();

        $query = Mockery::mock(Query::class);
        $query->expects('toIterable')->andReturn([
            ['id' => $dossierAId],
            ['id' => $dossierBId],
        ]);

        $this->dossierRepository->expects('getAllDossierIdsQuery')->andReturn($query);

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (IngestDossierCommand $command) use ($dossierAId) {
                self::assertEquals($dossierAId, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->messageBus->expects('dispatch')->with(Mockery::on(
            static function (IngestDossierCommand $command) use ($dossierBId) {
                self::assertEquals($dossierBId, $command->uuid);

                return true;
            }
        ))->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestDossierCommandForAllDossiers();
    }

    public function testDispatchIngestMetadataOnlyCommand(): void
    {
        $entityId = Uuid::v6();
        $entityClass = Document::class;
        $refresh = true;

        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::on(
                static function (IngestMetadataOnlyCommand $command) use ($entityClass, $entityId, $refresh) {
                    self::assertEquals($entityId, $command->getEntityId());
                    self::assertEquals($entityClass, $command->getEntityClass());
                    self::assertEquals($refresh, $command->getForceRefresh());

                    return true;
                }
            ))
            ->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestMetadataOnlyCommand($entityId, $entityClass, $refresh);
    }

    public function testDispatchIngestMetadataOnlyCommandForEntity(): void
    {
        $entityId = Uuid::v6();
        $entity = Mockery::mock(Document::class);
        $entity->shouldReceive('getId')->andReturn($entityId);
        $refresh = true;

        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::on(
                static function (IngestMetadataOnlyCommand $command) use ($entityId, $refresh) {
                    self::assertEquals($entityId, $command->getEntityId());
                    self::assertEquals($refresh, $command->getForceRefresh());

                    return true;
                }
            ))
            ->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestMetadataOnlyCommandForEntity($entity, $refresh);
    }

    public function testDispatchIngestPdfCommand(): void
    {
        $entityId = Uuid::v6();
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn($entityId);
        $refresh = true;

        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::on(
                static function (IngestPdfCommand $command) use ($entityId, $refresh) {
                    self::assertEquals($entityId, $command->getEntityId());
                    self::assertEquals($refresh, $command->getForceRefresh());

                    return true;
                }
            ))
            ->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestPdfCommand($entity, $refresh);
    }

    public function testDispatchIngestPdfPageCommand(): void
    {
        $entityId = Uuid::v6();
        $entityClass = Document::class;
        $page = 123;

        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::on(
                static function (IngestPdfPageCommand $command) use ($entityClass, $entityId, $page) {
                    self::assertEquals($entityId, $command->getEntityId());
                    self::assertEquals($entityClass, $command->getEntityClass());
                    self::assertEquals($page, $command->getPageNr());

                    return true;
                }
            ))
            ->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestPdfPageCommand($entityId, $entityClass, $page);
    }

    public function testDispatchIngestTikaOnlyCommand(): void
    {
        $entityId = Uuid::v6();
        $entity = Mockery::mock(EntityWithFileInfo::class);
        $entity->shouldReceive('getId')->andReturn($entityId);
        $refresh = true;

        $this->messageBus
            ->expects('dispatch')
            ->with(Mockery::on(
                static function (IngestTikaOnlyCommand $command) use ($entityId, $refresh) {
                    self::assertEquals($entityId, $command->getEntityId());
                    self::assertEquals($refresh, $command->getForceRefresh());

                    return true;
                }
            ))
            ->andReturns(new Envelope(new stdClass()));

        $this->dispatcher->dispatchIngestTikaOnlyCommand($entity, $refresh);
    }
}
