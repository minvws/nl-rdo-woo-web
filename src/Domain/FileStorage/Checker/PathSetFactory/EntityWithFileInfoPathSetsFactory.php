<?php

declare(strict_types=1);

namespace App\Domain\FileStorage\Checker\PathSetFactory;

use App\Domain\FileStorage\Checker\FileStorageType;
use App\Domain\FileStorage\Checker\PathSet;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\Inventory;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReport;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportProcessRun;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Service\Storage\ThumbnailStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class EntityWithFileInfoPathSetsFactory implements PathSetsFactoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ThumbnailStorageService $thumbnailStorageService,
        private int $thumbnailLimit,
    ) {
    }

    public function getPathSets(): \Generator
    {
        yield from $this->getPathSetsForEntity('Document', Document::class);
        yield from $this->getPathSetsForEntity('DocumentFileUpload', DocumentFileUpload::class);
        yield from $this->getPathSetsForEntity('DocumentFileUpdate', DocumentFileUpdate::class);
        yield from $this->getPathSetsForEntity('Attachment', AbstractAttachment::class);
        yield from $this->getPathSetsForEntity('MainDocument', AbstractMainDocument::class);
        yield from $this->getPathSetsForEntity('Inventory', Inventory::class);
        yield from $this->getPathSetsForEntity('InquiryInventory', InquiryInventory::class);
        yield from $this->getPathSetsForEntity('ProductionReport', ProductionReport::class);
        yield from $this->getPathSetsForEntity('ProductionReportProcessRun', ProductionReportProcessRun::class);
    }

    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     *
     * @return \Generator<PathSet>
     */
    private function getPathSetsForEntity(string $name, string $entityClass): \Generator
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->createQueryBuilder('e')->getQuery()->toIterable();

        $filePaths = [];
        $thumbPaths = [];
        foreach ($entities as $entity) {
            Assert::isInstanceOf($entity, EntityWithFileInfo::class);
            $path = $entity->getFileInfo()->getPath();

            if ($path !== null && $entity->getFileInfo()->isUploaded()) {
                $uuid = $entity->getId()->toRfc4122();
                $filePaths[$path] = $uuid;
                if ($entity->getFileInfo()->hasPages()) {
                    for ($i = 1; $i <= $entity->getFileInfo()->getPageCount() && $i <= $this->thumbnailLimit; $i++) {
                        $thumbPaths[$this->thumbnailStorageService->generateThumbPath($entity, $i)] = $uuid;
                    }
                }
            }

            $this->entityManager->detach($entity);
        }

        if ($filePaths !== []) {
            yield new PathSet($name, FileStorageType::DOCUMENT, $filePaths);
        }

        if ($thumbPaths !== []) {
            yield new PathSet($name . 'Thumb', FileStorageType::DOCUMENT, $thumbPaths);
        }
    }
}
