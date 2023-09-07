<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Entity\Document;
use App\Service\Storage\DocumentStorageService;
use App\Service\Worker\Pdf\Tools\FileUtils;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Extractor that will extract the page count of a PDF document.
 */
class PagecountExtractor implements DocumentExtractorInterface, OutputExtractorInterface
{
    protected LoggerInterface $logger;
    protected DocumentStorageService $documentStorage;
    protected FileUtils $fileUtils;
    protected Client $redis;

    /** @var array<string, int> */
    protected array $output = [];

    public function __construct(LoggerInterface $logger, DocumentStorageService $documentStorage, Client $redis)
    {
        $this->logger = $logger;
        $this->documentStorage = $documentStorage;

        $this->fileUtils = new FileUtils();
        $this->redis = $redis;
    }

    public function extract(Document $document, bool $forceRefresh): void
    {
        if ($forceRefresh || ! $this->isCached($document)) {
            $this->output['count'] = $this->extractPageCountFromPdf($document);
            $this->setCachedPageCount($document, $this->output['count']);
        }

        $this->output['count'] = $this->getCachedPageCount($document);
    }

    protected function getCachedPageCount(Document $document): int
    {
        $key = $document->getDocumentNr() . '_pagecount';

        return intval($this->redis->get($key));
    }

    protected function setCachedPageCount(Document $document, int $count): void
    {
        $key = $document->getDocumentNr() . '_pagecount';

        $this->redis->set($key, $count);
    }

    protected function extractPageCountFromPdf(Document $document): int
    {
        $localPdfPath = $this->documentStorage->downloadDocument($document);
        if (! $localPdfPath) {
            $this->logger->error('Failed to download document for page count extraction', [
                'document' => $document->getDocumentNr(),
            ]);

            return 0;
        }

        $params = ['/usr/bin/pdftk', $localPdfPath, 'dump_data'];
        $process = new Process($params);
        $process->run();

        $this->documentStorage->removeDownload($localPdfPath);

        if (! $process->isSuccessful()) {
            $this->logger->error('Failed to get page count: ', [
                'sourcePath' => $localPdfPath,
                'error_output' => $process->getErrorOutput(),
            ]);

            return 0;
        }

        $output = $process->getOutput();
        preg_match('/NumberOfPages: (\d+)/', $output, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    /**
     * @return array<string, int>
     */
    public function getOutput(Document $document, int $pageNr): array
    {
        return $this->output;
    }

    protected function isCached(Document $document): bool
    {
        $key = $document->getDocumentNr() . '_pagecount';

        return $this->redis->exists($key) == 1;
    }
}
