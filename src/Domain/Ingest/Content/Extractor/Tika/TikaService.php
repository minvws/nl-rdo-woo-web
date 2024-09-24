<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

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
    public function extract(string $sourcePath, string $contentType = 'application/pdf'): array
    {
        try {
            $result = $this->client
                ->put(
                    '/tika/text',
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => $contentType,
                        ],
                        'body' => file_get_contents($sourcePath),
                    ],
                );
        } catch (GuzzleException $e) {
            $this->logger->error('Tika failed', [
                'sourcePath' => $sourcePath,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }

        $content = $result->getBody()->getContents();
        $content = json_decode($content, true);

        if (! is_array($content)) {
            return [];
        }

        /** @var array<string,string> $content */
        return $content;
    }
}
