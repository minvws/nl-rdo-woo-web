<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;
use App\Service\Elastic\ElasticService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Worker\Pdf\Tools\FileUtils;
use App\Service\Worker\Pdf\Tools\Tesseract;
use App\Service\Worker\Pdf\Tools\Tika;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * Extractor that will extract content from a single page from a given document.
 */
class PageContentExtractor implements PageExtractorInterface
{
    protected LoggerInterface $logger;
    protected DocumentStorageService $documentStorage;
    protected FileUtils $fileUtils;
    protected Client $redis;
    protected ElasticService $elasticService;
    protected Tesseract $tesseract;
    protected Tika $tika;

    public function __construct(
        LoggerInterface $logger,
        DocumentStorageService $documentStorage,
        Client $redis,
        ElasticService $elasticService,
        Tesseract $tesseract,
        Tika $tika
    ) {
        $this->logger = $logger;
        $this->documentStorage = $documentStorage;
        $this->redis = $redis;
        $this->elasticService = $elasticService;
        $this->tesseract = $tesseract;
        $this->tika = $tika;

        $this->fileUtils = new FileUtils();
    }

    public function extract(Document $document, int $pageNr, bool $forceRefresh): void
    {
        if ($forceRefresh || ! $this->isCached($document, $pageNr)) {
            list($content, $tikaData) = $this->extractContentFromPdf($document, $pageNr);

            $this->setCachedContent($document, $pageNr, $content, $tikaData);
        }

        list($content, $tikaData) = $this->getCachedContent($document, $pageNr);

        $this->indexPage($document, $pageNr, $content, $tikaData);
    }

    /**
     * @return array{string, array<string,string>}
     */
    protected function extractContentFromPdf(Document $document, int $pageNr): array
    {
        $localPdfPath = $this->documentStorage->downloadPage($document, $pageNr);
        if (! $localPdfPath) {
            $this->logger->error('Failed to save document to local storage: ' . $document->getDocumentNr() . ' - ' . $pageNr);

            return ['', []];
        }

        $pageContent = [];

        $tikaData = $this->tika->extract($localPdfPath);
        $pageContent[] = $tikaData['X-TIKA:content'] ?? '';
        $pageContent[] = $this->tesseract->extract($localPdfPath);

        $this->documentStorage->removeDownload($localPdfPath);

        return [join("\n", $pageContent), $tikaData];
    }

    protected function getCacheKey(Document $document, int $pageNr, string $suffix): string
    {
        return $document->getDocumentNr() . '-' . $pageNr . '-' . $suffix;
    }

    /**
     * @param array<string,string> $tikaData
     */
    protected function indexPage(Document $document, int $pageNr, string $content, array $tikaData): void
    {
        // Unset the content from the tika data, as we already store that separately
        unset($tikaData['X-TIKA:content']);

        try {
            $this->elasticService->updatePage($document, $pageNr, $content);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index page', [
                'document' => $document->getDocumentNr(),
                'page' => $pageNr,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{string, array<string,string>}
     */
    protected function getCachedContent(Document $document, int $pageNr): array
    {
        $key = $this->getCacheKey($document, $pageNr, 'content');
        $content = $this->redis->get($key);

        $key = $this->getCacheKey($document, $pageNr, 'tikadata');
        $tikadata = json_decode(strval($this->redis->get($key)), true);

        /** @var array<string,string> $tikadata */
        return [$content ?? '', $tikadata];
    }

    /**
     * @param string[] $tikaData
     */
    protected function setCachedContent(Document $document, int $pageNr, string $content, array $tikaData): void
    {
        $key = $this->getCacheKey($document, $pageNr, 'content');
        $this->redis->set($key, $content);
        $key = $this->getCacheKey($document, $pageNr, 'tikadata');
        $this->redis->set($key, json_encode($tikaData));
    }

    protected function isCached(Document $document, int $pageNr): bool
    {
        $key1 = $this->getCacheKey($document, $pageNr, 'content');
        $key2 = $this->getCacheKey($document, $pageNr, 'tikadata');

        return $this->redis->exists($key1) == 1 && $this->redis->exists($key2) == 1;
    }
}
