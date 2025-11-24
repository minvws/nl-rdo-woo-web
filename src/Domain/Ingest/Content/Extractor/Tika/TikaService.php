<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;

readonly class TikaService
{
    public function __construct(
        private Client $client,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string,string>
     */
    public function extract(
        string $sourcePath,
        string $contentType = 'application/pdf',
        ?ContentExtractLogContext $logContext = null,
    ): array {
        try {
            $result = $this->client
                ->put(
                    '/tika/text',
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => $contentType,
                            'X-Tika-OCRmaxFileSizeToOcr' => 0,
                        ],
                        'body' => file_get_contents($sourcePath),
                    ],
                );
        } catch (GuzzleException $e) {
            $this->logger->error('Tika failed', [
                'sourcePath' => $sourcePath,
                'exception' => $e->getMessage(),
                'context' => $logContext,
            ]);

            return [];
        }

        $content = $result->getBody()->getContents();
        $content = json_decode($content, true);

        /** @var array<string,string> $content */
        return is_array($content)
            ? $content
            : [];
    }
}
