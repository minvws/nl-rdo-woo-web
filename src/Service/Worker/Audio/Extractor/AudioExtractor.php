<?php

declare(strict_types=1);

namespace App\Service\Worker\Audio\Extractor;

use App\Entity\Document;
use App\Service\Elastic\ElasticService;
use App\Service\Storage\DocumentStorageService;
use App\Service\Worker\Pdf\Tools\FileUtils;
use App\Service\Worker\Pdf\Tools\Tesseract;
use App\Service\Worker\Pdf\Tools\Tika;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Extractor that will extract and store content from a audio file.
 */
class AudioExtractor implements AudioExtractorInterface
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
    ) {
        $this->logger = $logger;
        $this->documentStorage = $documentStorage;
        $this->redis = $redis;
        $this->elasticService = $elasticService;

        $this->fileUtils = new FileUtils();
    }

    public function extract(Document $document, bool $forceRefresh): void
    {
        if ($forceRefresh || ! $this->isCached($document)) {
            list($content, $metaData) = $this->extractContentFromAudio($document);

            /** @var string[] $metaData */
            $this->setCachedContent($document, $content, $metaData);
        }

        list($content, $metaData) = $this->getCachedContent($document);
        $this->indexDocument($document, $content, $metaData);
    }

    /**
     * @return array{string, mixed[]}
     */
    protected function extractContentFromAudio(Document $document): array
    {
        $localAudioPath = $this->documentStorage->downloadDocument($document);
        if (! $localAudioPath) {
            $this->logger->error('Failed to save document to local storage', [
                'document' => $document->getId(),
            ]);

            return ['', []];
        }

        $metaData = $this->extractMetadata($localAudioPath);

        $content = $this->extractText($localAudioPath);

        $this->documentStorage->removeDownload($localAudioPath);

        return [$content, $metaData];
    }

    protected function getCacheKey(Document $document, string $suffix): string
    {
        return $document->getDocumentNr() . '-' . $suffix;
    }

    /**
     * @param array<string,string> $metadata
     */
    protected function indexDocument(Document $document, string $content, array $metadata): void
    {
        unset($content);

        try {
            $this->elasticService->updateDocument($document, $metadata);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create document', [
                'document' => $document->getDocumentNr(),
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{string, array<string,string>}
     */
    protected function getCachedContent(Document $document): array
    {
        $key = $this->getCacheKey($document, 'content');
        $content = $this->redis->get($key);

        $key = $this->getCacheKey($document, 'metadata');
        $metadata = json_decode(strval($this->redis->get($key)), true);

        /** @var array<string,string> $metadata */
        return [$content ?? '', $metadata];
    }

    /**
     * @param string[] $metadata
     */
    protected function setCachedContent(Document $document, string $content, array $metadata): void
    {
        $key = $this->getCacheKey($document, 'content');
        $this->redis->set($key, $content);
        $key = $this->getCacheKey($document, 'metadata');
        $this->redis->set($key, json_encode($metadata));
    }

    protected function isCached(Document $document): bool
    {
        $key1 = $this->getCacheKey($document, 'content');
        $key2 = $this->getCacheKey($document, 'metadata');

        return $this->redis->exists($key1) == 1 && $this->redis->exists($key2) == 1;
    }

    /**
     * @return mixed[]
     */
    protected function extractMetadata(string $localAudioPath): array
    {
        $params = [
            '/usr/bin/ffprobe',
            '-v',
            'quiet',
            '-print_format',
            'json',
            '-show_format',
            '-show_streams',
            $localAudioPath,
        ];
        $process = new Process($params);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->logger->error('Failed to get audio metadata: ', [
                'sourcePath' => $localAudioPath,
                'error_output' => $process->getErrorOutput(),
            ]);

            return [];
        }

        $output = $process->getOutput();
        $data = json_decode($output, true);
        if ($data === false) {
            $this->logger->error('Failed to parse audio metadata: ', [
                'sourcePath' => $localAudioPath,
                'error_output' => $process->getErrorOutput(),
            ]);

            return [];
        }

        /** @var array<string, string[]> $data */
        // Filename leaks worker path information, so remove it
        unset($data['format']['filename']);

        return $data;
    }

    protected function extractText(string $localAudioPath): string
    {
        unset($localAudioPath);

        return 'this is some dummy content taken from an audio file';
    }
}
