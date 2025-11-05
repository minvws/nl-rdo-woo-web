<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\AntiVirus;

use App\Domain\Upload\AntiVirus\ClamAvClientFactory;
use App\Domain\Upload\AntiVirus\ClamAvPlatformChecker;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

final class ClamAvPlatformCheckerTest extends MockeryTestCase
{
    private Client&MockInterface $client;
    private ClamAvClientFactory&MockInterface $clientFactory;
    private ClamAvPlatformChecker $checker;

    protected function setUp(): void
    {
        $this->client = \Mockery::mock(Client::class);

        $this->clientFactory = \Mockery::mock(ClamAvClientFactory::class);
        $this->clientFactory->shouldReceive('getClient')->andReturn($this->client);

        $this->checker = new ClamAvPlatformChecker($this->clientFactory);

        parent::setUp();
    }

    public function testCheckerSuccess(): void
    {
        $this->client->expects('scanStream')->andReturn(new Result('FOUND', '', null, null));

        self::assertTrue($this->checker->getResults()[0]->successful);
    }

    public function testCheckerFailureOnException(): void
    {
        $this->client->expects('scanStream')->andThrow(new \RuntimeException());

        self::assertFalse($this->checker->getResults()[0]->successful);
    }

    public function testCheckerFailureOnNoDetection(): void
    {
        $this->client->expects('scanStream')->andReturn(new Result('OK', '', null, null));

        self::assertFalse($this->checker->getResults()[0]->successful);
    }
}
