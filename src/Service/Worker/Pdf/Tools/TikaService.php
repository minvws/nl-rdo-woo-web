<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Calls tika (as a service) to extract text from a PDF.
 */
class TikaService
{
    public function __construct(
        protected GuzzleClient $tika,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string,string>
     */
    public function extract(string $sourcePath, string $contentType = 'application/pdf'): array
    {
        try {
            $result = $this->tika
                ->put(
                    '/tika/text',
                    [
                        'headers' => [
                            'Accept' => 'application/json',
                            'Content-Type' => $contentType,
                        ],
                        'body' => fopen($sourcePath, 'r'),
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

        /** @var array<string,string> $content */
        return $content;
    }
}
