<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\ContentExtractLogContext;

use function is_array;
use function json_decode;

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
        StreamInterface $source,
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
                            'X-Tika-OCRmaxFileSizeToOcr' => '0',
                        ],
                        'body' => $source,
                    ],
                );
        } catch (GuzzleException $e) {
            $this->logger->error('Tika failed', [
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
