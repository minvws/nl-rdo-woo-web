<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Ingest\Content\Extractor\Tika;

use GuzzleHttp\Client;
use Shared\Domain\Ingest\Content\Extractor\Tika\TikaGuzzleClientFactory;
use Shared\Tests\Unit\UnitTestCase;

final class TikaGuzzleClientFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $clinet = TikaGuzzleClientFactory::create('http://localhost:9998');

        $this->assertInstanceOf(Client::class, $clinet);
    }
}
