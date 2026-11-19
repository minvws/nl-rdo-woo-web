<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use LogicException;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inventory\DocumentMetadata;
use Shared\Service\Inventory\InventoryService;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_find_key;
use function array_key_exists;
use function array_map;
use function count;
use function is_a;
use function is_string;
use function sprintf;

#[AsCommand(name: self::COMMAND_NAME, description: 'Recalculates source types for files currently marked as DOC')]
final class RecalculateFileSourceTypes extends Command
{
    public const string COMMAND_NAME = 'woopie:recalculate-file-source-types';

    private const int BATCH_SIZE = 1000;
    private int $restoredCount = 0;
    private int $checkedCount = 0;
    private int $updatedCount = 0;
    private int $reindexCount = 0;
    private int $withoutIndexCount = 0;

    /**
     * @var array<string, string>
     */
    private array $failedDossiers = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IngestDispatcher $ingestDispatcher,
        private readonly InventoryService $inventoryService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show changes without updating the database or Elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry-run');
        Assert::boolean($dryRun);

        $this->restoreDocumentSourceTypes($dryRun);
        $this->recalculate($dryRun);
        $this->writeSummary($output, $dryRun);

        if (count($this->failedDossiers) > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function restoreDocumentSourceTypes(bool $dryRun): void
    {
        foreach ($this->getDossierIdsWithProductionReport() as $dossierId) {
            $dossier = $this->entityManager->find(WooDecision::class, $dossierId);
            Assert::isInstanceOf($dossier, WooDecision::class);

            $this->restoreDossierDocumentSourceTypes($dossier, $dryRun);
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getDossierIdsWithProductionReport(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('DISTINCT dossier.id')
            ->from(WooDecision::class, 'dossier')
            ->innerJoin('dossier.documents', 'document')
            ->innerJoin('dossier.productionReport', 'productionReport')
            ->getQuery()
            ->getSingleColumnResult();
    }

    private function restoreDossierDocumentSourceTypes(WooDecision $dossier, bool $dryRun): void
    {
        try {
            $productionReportSourceTypes = $this->getProductionReportSourceTypes($dossier);
        } catch (Exception $exception) {
            $this->failedDossiers[$dossier->getId()->toRfc4122()] = $exception->getMessage();
            $this->entityManager->clear();

            return;
        }

        $documentsToReindex = new ArrayCollection();
        $documentCount = 0;
        foreach ($this->getDossierDocuments($dossier) as $document) {
            Assert::isInstanceOf($document, Document::class);

            $documentCount++;
            $documentNumber = $document->getDocumentNumber()->toString();
            if (array_key_exists($documentNumber, $productionReportSourceTypes)) {
                $this->restoreSourceType($document, $productionReportSourceTypes[$documentNumber], $dryRun, $documentsToReindex);
            }

            $this->flushBatchIfNeeded($documentCount, $documentsToReindex, $dryRun);
        }

        $this->flushAndClear($documentsToReindex, $dryRun);
    }

    /**
     * @return array<string, SourceType>
     */
    private function getProductionReportSourceTypes(WooDecision $dossier): array
    {
        $productionReport = $dossier->getProductionReport();
        Assert::isInstanceOf($productionReport, ProductionReport::class);

        $sourceTypes = [];
        $inventoryReader = $this->inventoryService->getReader($productionReport);
        foreach ($inventoryReader->getDocumentMetadataGenerator($dossier) as $inventoryReadItem) {
            $documentMetadata = $inventoryReadItem->getDocumentMetadata();
            if (! $documentMetadata instanceof DocumentMetadata) {
                continue;
            }

            $documentNumber = DocumentNumber::fromPublicationContextAndDocumentId(
                $documentMetadata->getPublicationContext(),
                $documentMetadata->getId(),
            )->toString();
            $sourceTypes[$documentNumber] = $documentMetadata->getSourceType();
        }

        return $sourceTypes;
    }

    /**
     * @return iterable<mixed>
     */
    private function getDossierDocuments(WooDecision $dossier): iterable
    {
        return $this->entityManager->createQueryBuilder()
            ->select('document')
            ->from(Document::class, 'document')
            ->where(':dossier MEMBER OF document.dossiers')
            ->setParameter('dossier', $dossier)
            ->getQuery()
            ->toIterable();
    }

    /**
     * @param Collection<int, EntityWithFileInfo> $documentsToReindex
     */
    private function restoreSourceType(
        Document $document,
        SourceType $productionReportSourceType,
        bool $dryRun,
        Collection $documentsToReindex,
    ): void {
        $sourceType = $this->resolveRestoredSourceType($document, $productionReportSourceType);
        if ($document->getFileInfo()->getSourceType() === $sourceType) {
            return;
        }

        $this->restoredCount++;
        if ($dryRun) {
            return;
        }

        $document->getFileInfo()->setSourceType($sourceType);
        $documentsToReindex->add($document);
    }

    private function resolveRestoredSourceType(Document $document, SourceType $productionReportSourceType): SourceType
    {
        if ($productionReportSourceType !== SourceType::DOC) {
            return $productionReportSourceType;
        }

        if (SourceType::fromMimeType($document->getFileInfo()->getMimetype()) !== SourceType::TXT) {
            return $productionReportSourceType;
        }

        return SourceType::TXT;
    }

    private function recalculate(bool $dryRun): void
    {
        $entitiesToReindex = new ArrayCollection();

        foreach ($this->getEntities() as $entity) {
            $this->processEntity($entity, $dryRun, $entitiesToReindex);
        }

        $this->flushAndClear($entitiesToReindex, $dryRun);
    }

    /**
     * @param Collection<int, EntityWithFileInfo> $entitiesToReindex
     */
    private function processEntity(
        mixed $entity,
        bool $dryRun,
        Collection $entitiesToReindex,
    ): void {
        Assert::isInstanceOf($entity, EntityWithFileInfo::class);

        $this->checkedCount++;
        $this->recalculateSourceType($entity, $dryRun, $entitiesToReindex);
        $this->flushBatchIfNeeded($this->checkedCount, $entitiesToReindex, $dryRun);
    }

    /**
     * @param Collection<int, EntityWithFileInfo> $entitiesToReindex
     */
    private function recalculateSourceType(
        EntityWithFileInfo $entity,
        bool $dryRun,
        Collection $entitiesToReindex,
    ): void {
        $sourceType = SourceType::fromMimeType($entity->getFileInfo()->getMimetype());
        if ($sourceType !== SourceType::TXT) {
            return;
        }

        $this->updatedCount++;

        if (! $dryRun) {
            $entity->getFileInfo()->setSourceType($sourceType);
        }

        if (! $this->isSearchIndexed($entity)) {
            $this->withoutIndexCount++;

            return;
        }

        $this->reindexCount++;
        if (! $dryRun) {
            $entitiesToReindex->add($entity);
        }
    }

    /**
     * @param Collection<int, EntityWithFileInfo> $entitiesToReindex
     */
    private function flushBatchIfNeeded(
        int $count,
        Collection $entitiesToReindex,
        bool $dryRun,
    ): void {
        if ($count % self::BATCH_SIZE !== 0) {
            return;
        }

        $this->flushAndClear($entitiesToReindex, $dryRun);
    }

    /**
     * @param Collection<int, EntityWithFileInfo> $entitiesToReindex
     */
    private function flushAndClear(
        Collection $entitiesToReindex,
        bool $dryRun,
    ): void {
        $this->flushAndDispatch($entitiesToReindex, $dryRun);
        $entitiesToReindex->clear();
        $this->entityManager->clear();
    }

    /**
     * @return iterable<class-string<EntityWithFileInfo>>
     */
    private function getEntityClasses(): iterable
    {
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        return array_map(
            $this->getEntityClassName(...),
            array_filter($metadata, $this->isRootFileInfoEntity(...)),
        );
    }

    private function isRootFileInfoEntity(ClassMetadata $metadata): bool
    {
        return ! $metadata->isMappedSuperclass
            && ! $metadata->isEmbeddedClass
            && $metadata->rootEntityName === $metadata->getName()
            && is_a($metadata->getName(), EntityWithFileInfo::class, true);
    }

    /**
     * @return class-string<EntityWithFileInfo>
     */
    private function getEntityClassName(ClassMetadata $metadata): string
    {
        $entityClass = $metadata->getName();
        Assert::implementsInterface($entityClass, EntityWithFileInfo::class);

        return $entityClass;
    }

    /**
     * @return iterable<mixed>
     */
    private function getEntities(): iterable
    {
        foreach ($this->getEntityClasses() as $entityClass) {
            yield from $this->getEntitiesOfClass($entityClass);
        }
    }

    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     *
     * @return iterable<mixed>
     */
    private function getEntitiesOfClass(string $entityClass): iterable
    {
        $metadata = $this->entityManager->getClassMetadata($entityClass);
        $fileInfoProperty = $this->getFileInfoProperty($metadata);
        $queryBuilder = $this->entityManager->getRepository($entityClass)
            ->createQueryBuilder('entity')
            ->where(sprintf('entity.%s.sourceType = :sourceType', $fileInfoProperty))
            ->setParameter('sourceType', SourceType::DOC->value);

        if ($entityClass === Document::class) {
            $queryBuilder->andWhere($queryBuilder->expr()->not(
                $queryBuilder->expr()->exists($this->getProductionReportDossierOfEntityDql()),
            ));
        }

        yield from $queryBuilder->getQuery()->toIterable();
    }

    private function getProductionReportDossierOfEntityDql(): string
    {
        return $this->entityManager->createQueryBuilder()
            ->select('productionReportDossier.id')
            ->from(WooDecision::class, 'productionReportDossier')
            ->innerJoin('productionReportDossier.productionReport', 'productionReport')
            ->where('productionReportDossier MEMBER OF entity.dossiers')
            ->getDQL();
    }

    private function getFileInfoProperty(ClassMetadata $metadata): string
    {
        $property = array_find_key(
            $metadata->embeddedClasses,
            static fn ($mapping): bool => $mapping->class === FileInfo::class,
        );
        if (! is_string($property)) {
            throw new LogicException(sprintf('No %s embedding found for %s', FileInfo::class, $metadata->getName()));
        }

        return $property;
    }

    private function isSearchIndexed(EntityWithFileInfo $entity): bool
    {
        return $entity instanceof Document
            || $entity instanceof AbstractAttachment
            || $entity instanceof AbstractMainDocument;
    }

    /**
     * @param Collection<int, EntityWithFileInfo> $entitiesToReindex
     */
    private function flushAndDispatch(Collection $entitiesToReindex, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $this->entityManager->flush();

        foreach ($entitiesToReindex as $entity) {
            $this->ingestDispatcher->dispatchIngestMetadataOnlyCommandForEntity($entity, forceRefresh: false);
        }
    }

    private function writeSummary(
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $output->writeln(sprintf(
            '<info>%s %d document source types from production reports.</info>',
            $dryRun ? 'Would restore' : 'Restored',
            $this->restoredCount,
        ));

        foreach ($this->failedDossiers as $dossierId => $message) {
            $output->writeln(sprintf(
                '<error>Could not read the production report of dossier %s: %s</error>',
                $dossierId,
                $message,
            ));
        }

        $output->writeln(sprintf(
            '<info>Checked %d DOC records, %s %d source types and %s %d Elasticsearch '
            . 'reindex messages; %d changed records have no Elasticsearch document.</info>',
            $this->checkedCount,
            $dryRun ? 'would update' : 'updated',
            $this->updatedCount,
            $dryRun ? 'would dispatch' : 'dispatched',
            $this->reindexCount,
            $this->withoutIndexCount,
        ));

        if ($dryRun) {
            $output->writeln('<comment>Dry run: no database updates or Elasticsearch reindex messages were dispatched.</comment>');
        }
    }
}
