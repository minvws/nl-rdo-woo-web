<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use App\Service\Storage\EntityStorageService;
use App\Service\Worker\Pdf\Tools\TesseractService;
use App\Service\Worker\Pdf\Tools\TikaService;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Extractor that will extract content from a single page from a given entity.
 */
readonly class PageContentExtractor implements PageExtractorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityStorageService $entityStorageService,
        private SubTypeIndexer $subTypeIndexer,
        private TesseractService $tesseract,
        private TikaService $tika,
        private WorkerStatsService $statsService,
    ) {
    }

    public function extract(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void
    {
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        // TODO: Cache is removed for #2142, to be improved and restored in #2144
        unset($forceRefresh);
        $content = $this->extractContentFromPdf($entity, $pageNr);

        $this->statsService->measure(
            'index.full.entity',
            fn () => $this->indexPage($entity, $pageNr, $content),
        );
    }

    private function extractContentFromPdf(EntityWithFileInfo $entity, int $pageNr): string
    {
        /** @var string|false $localPdfPath */
        $localPdfPath = $this->statsService->measure(
            'download.entity',
            fn () => $this->entityStorageService->downloadPage($entity, $pageNr),
        );

        if ($localPdfPath === false) {
            $this->logger->error('Failed to save entity to local storage', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pageNr,
            ]);

            return '';
        }

        /** @var array<array-key,string> $tikaData */
        $tikaData = $this->statsService->measure('tika', fn (): array => $this->tika->extract($localPdfPath));

        /** @var string $tesseractContent */
        $tesseractContent = $this->statsService->measure(
            'tesseract',
            fn (): string => $this->tesseract->extract($localPdfPath),
        );

        $this->entityStorageService->removeDownload($localPdfPath);

        return join("\n", [$tikaData['X-TIKA:content'] ?? '', $tesseractContent]);
    }

    private function indexPage(EntityWithFileInfo $entity, int $pageNr, string $content): void
    {
        try {
            $this->subTypeIndexer->updatePage($entity, $pageNr, $content);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pageNr,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
