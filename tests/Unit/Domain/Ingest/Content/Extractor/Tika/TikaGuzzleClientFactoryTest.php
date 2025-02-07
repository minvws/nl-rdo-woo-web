<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use App\Domain\Ingest\Content\Extractor\Tika\TikaGuzzleClientFactory;
use App\Tests\Unit\UnitTestCase;
use GuzzleHttp\Client;

final class TikaGuzzleClientFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $clinet = TikaGuzzleClientFactory::create('http://localhost:9998');

        $this->assertInstanceOf(Client::class, $clinet);
    }
}
