<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\AntiVirus;

use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Shared\Domain\Upload\AntiVirus\ClamAvClientFactory;
use Shared\Domain\Upload\AntiVirus\ClamAvPlatformChecker;
use Shared\Tests\Unit\UnitTestCase;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

final class ClamAvPlatformCheckerTest extends UnitTestCase
{
    private Client&MockInterface $client;
    private ClamAvClientFactory&MockInterface $clientFactory;
    private ClamAvPlatformChecker $checker;

    protected function setUp(): void
    {
        $this->client = Mockery::mock(Client::class);

        $this->clientFactory = Mockery::mock(ClamAvClientFactory::class);
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
        $this->client->expects('scanStream')->andThrow(new RuntimeException());

        self::assertFalse($this->checker->getResults()[0]->successful);
    }

    public function testCheckerFailureOnNoDetection(): void
    {
        $this->client->expects('scanStream')->andReturn(new Result('OK', '', null, null));

        self::assertFalse($this->checker->getResults()[0]->successful);
    }
}
