<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;
use App\Service\Elastic\ElasticService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Worker\Pdf\Tools\Tesseract;
use App\Service\Worker\Pdf\Tools\Tika;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * Extractor that will extract and store content from a multi-paged PDF document.
 */
class DocumentContentExtractor implements DocumentExtractorInterface
{
    protected LoggerInterface $logger;
    protected DocumentStorageService $documentStorage;
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
    }

    public function extract(Document $document, bool $forceRefresh): void
    {
        if ($forceRefresh || ! $this->isCached($document)) {
            $tikaData = $this->extractContentFromPdf($document);

            $this->setCachedTikaData($document, $tikaData);
        }

        $tikaData = $this->getCachedTikaData($document);
        $this->indexDocument($document, $tikaData);
    }

    /**
     * @return array{string, array<string,string>}
     */
    protected function extractContentFromPdf(Document $document): array
    {
        $localPdfPath = $this->documentStorage->downloadDocument($document);
        if (! $localPdfPath) {
            $this->logger->error('Failed to save document to local storage', [
                'document' => $document->getId(),
            ]);

            return ['', []];
        }

        $tikaData = $this->tika->extract($localPdfPath);
        $documentContent = $tikaData['X-TIKA:content'] ?? '';

        $this->documentStorage->removeDownload($localPdfPath);

        return [$documentContent, $tikaData];
    }

    protected function getCacheKey(Document $document, string $suffix): string
    {
        return $document->getDocumentNr() . '-' . $suffix;
    }

    /**
     * @param array<string,string> $tikaData
     */
    protected function indexDocument(Document $document, array $tikaData): void
    {
        // Unset the content from the tika data, as we already store that separately
        unset($tikaData['X-TIKA:content']);

        try {
            $this->elasticService->updateDocument($document, $tikaData);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create document', [
                'document' => $document->getDocumentNr(),
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string,string>
     */
    protected function getCachedTikaData(Document $document): array
    {
        $key = $this->getCacheKey($document, 'tikadata');
        $tikaData = json_decode(strval($this->redis->get($key)), true, 512, JSON_THROW_ON_ERROR);

        /** @var array<string,string> $tikaData */
        return $tikaData;
    }

    /**
     * @param array{string, array<string,string>} $tikaData
     */
    protected function setCachedTikaData(Document $document, array $tikaData): void
    {
        $key = $this->getCacheKey($document, 'tikadata');
        $this->redis->set($key, json_encode($tikaData, JSON_THROW_ON_ERROR));
    }

    protected function isCached(Document $document): bool
    {
        $key = $this->getCacheKey($document, 'tikadata');

        return $this->redis->exists($key) == 1;
    }
}
