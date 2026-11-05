<?php

declare(strict_types=1);

namespace Shared\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use LogicException;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\FileInfo;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\SourceType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_find_key;
use function array_map;
use function is_a;
use function is_string;
use function sprintf;

#[AsCommand(name: self::COMMAND_NAME, description: 'Recalculates source types for files currently marked as DOC')]
final class RecalculateFileSourceTypes extends Command
{
    public const string COMMAND_NAME = 'woopie:recalculate-file-source-types';

    private const int BATCH_SIZE = 1000;
    private int $checkedCount = 0;
    private int $updatedCount = 0;
    private int $reindexCount = 0;
    private int $withoutIndexCount = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IngestDispatcher $ingestDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show changes without updating the database or Elasticsearch');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $this->isDryRun($input);
        $this->recalculate($dryRun);
        $this->writeSummary($output, $dryRun);

        return self::SUCCESS;
    }

    private function isDryRun(InputInterface $input): bool
    {
        $dryRun = $input->getOption('dry-run');
        Assert::boolean($dryRun);

        return $dryRun;
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
        $this->flushBatchIfNeeded($entitiesToReindex, $dryRun);
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
        if ($sourceType === SourceType::DOC) {
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
        Collection $entitiesToReindex,
        bool $dryRun,
    ): void {
        if ($this->checkedCount % self::BATCH_SIZE !== 0) {
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
        $query = $this->entityManager->getRepository($entityClass)
            ->createQueryBuilder('entity')
            ->where(sprintf('entity.%s.sourceType = :sourceType', $fileInfoProperty))
            ->setParameter('sourceType', SourceType::DOC->value)
            ->getQuery();

        yield from $query->toIterable();
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
