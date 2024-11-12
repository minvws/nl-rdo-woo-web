<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Domain\Ingest\Process\Dossier\IngestAllDossiersCommand;
use App\Domain\Ingest\Process\Dossier\IngestDossierCommand;
use App\Domain\Ingest\Process\MetadataOnly\IngestMetadataOnlyCommand;
use App\Domain\Ingest\Process\Pdf\IngestPdfCommand;
use App\Domain\Ingest\Process\PdfPage\IngestPdfPageCommand;
use App\Domain\Ingest\Process\TikaOnly\IngestTikaOnlyCommand;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Entity\EntityWithFileInfo;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class IngestDispatcher
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchIngestDossierCommand(AbstractDossier $dossier, bool $refresh = false): void
    {
        $this->messageBus->dispatch(
            new IngestDossierCommand($dossier->getId(), $refresh),
        );
    }

    public function dispatchIngestAllDossiersCommand(): void
    {
        $this->messageBus->dispatch(
            new IngestAllDossiersCommand(),
        );
    }

    public function dispatchIngestDossierCommandForAllDossiers(): void
    {
        // Important: use an iterator instead of fetching all into memory at once
        $query = $this->dossierRepository->getAllDossierIdsQuery();

        /** @var array{id:Uuid} $row */
        foreach ($query->toIterable() as $row) {
            // This will dispatch a message per dossier => per dossier doc => per doc page (fan-out)
            $this->messageBus->dispatch(
                new IngestDossierCommand($row['id'], false),
            );
        }
    }

    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     */
    public function dispatchIngestMetadataOnlyCommand(Uuid $entityId, string $entityClass, bool $forceRefresh): void
    {
        $this->messageBus->dispatch(
            new IngestMetadataOnlyCommand($entityId, $entityClass, $forceRefresh),
        );
    }

    public function dispatchIngestMetadataOnlyCommandForEntity(EntityWithFileInfo $entity, bool $forceRefresh): void
    {
        $this->messageBus->dispatch(
            IngestMetadataOnlyCommand::forEntity($entity, $forceRefresh),
        );
    }

    public function dispatchIngestPdfCommand(EntityWithFileInfo $entity, bool $forceRefresh): void
    {
        $this->messageBus->dispatch(
            IngestPdfCommand::forEntity($entity, $forceRefresh),
        );
    }

    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     */
    public function dispatchIngestPdfPageCommand(Uuid $entityId, string $entityClass, bool $forceRefresh, int $pageNr): void
    {
        $this->messageBus->dispatch(
            new IngestPdfPageCommand($entityId, $entityClass, $forceRefresh, $pageNr),
        );
    }

    public function dispatchIngestTikaOnlyCommand(EntityWithFileInfo $entity, bool $forceRefresh): void
    {
        $this->messageBus->dispatch(
            IngestTikaOnlyCommand::forEntity($entity, $forceRefresh),
        );
    }
}
