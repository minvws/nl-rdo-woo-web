<?php

declare(strict_types=1);

namespace Shared\Service\Worker\Pdf\Extractor;

use Psr\Log\LoggerInterface;
use Shared\Domain\HasId;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkService;

/**
 * Extractor that will extract the page count of a PDF entity.
 *
 * @implements OutputExtractorInterface<PdftkPageCountResult>
 */
class PagecountExtractor implements EntityExtractorInterface, OutputExtractorInterface
{
    protected ?PdftkPageCountResult $output = null;

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly PdftkService $pdftkService,
        protected readonly EntityStorageService $entityStorageService,
    ) {
    }

    public function extract(EntityWithFileInfo $entity): void
    {
        $this->output = $this->extractPageCountFromPdf($entity);
    }

    protected function extractPageCountFromPdf(EntityWithFileInfo&HasId $entity): ?PdftkPageCountResult
    {
        $source = $this->entityStorageService->retrieveResourceEntity($entity);
        if ($source === null) {
            $this->logger->error('Failed to read entity from storage for page count extraction', [
                'id' => $entity->getId(),
                'class' => $entity::class,
            ]);

            return null;
        }

        $pdftkPageCountResult = $this->pdftkService->extractNumberOfPages($source);

        if ($pdftkPageCountResult->isFailed()) {
            $this->logger->error('Failed to get number of pages', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'errorOutput' => $pdftkPageCountResult->errorMessage,
            ]);
        }

        return $pdftkPageCountResult;
    }

    public function getOutput(): ?PdftkPageCountResult
    {
        return $this->output;
    }
}
