<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Calls tika (as a service) to extract text from a PDF.
 */
class Tika
{
    protected GuzzleClient $tika;
    protected LoggerInterface $logger;

    public function __construct(GuzzleClient $tika, LoggerInterface $logger)
    {
        $this->tika = $tika;
        $this->logger = $logger;
    }

    /**
     * @return array|string[]
     */
    public function extract(string $sourcePath): array
    {
        try {
            $result = $this->tika->put('/tika/text', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/pdf',
                ],
                'body' => fopen($sourcePath, 'r'),
            ]);
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
        $this->logger->debug('Tika content: ' . strlen($content['X-TIKA:content'] ?? '') . ' bytes');

        return $content;
    }
}
