<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Client;

/**
 * Creates a configured Guzzle client for the Tika service.
 */
class TikaGuzzleClientFactory
{
    public static function create(string $tikaHost): Client
    {
        return new Client([
            'base_uri' => $tikaHost,
            'timeout' => 600.0,
            'http_errors' => false,
            'connect_timeout' => 5.0,
        ]);
    }
}
