<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\S3;

use App\Domain\S3\S3StreamFactory;
use App\Domain\S3\StreamMode;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class S3StreamFactoryTest extends UnitTestCase
{
    private S3StreamFactory&MockInterface $s3StreamFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->s3StreamFactory = \Mockery::mock(S3StreamFactory::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }

    public function testCreateReadOnlyStream(): void
    {
        $this->s3StreamFactory
            ->shouldReceive('doFopen')
            ->with('s3://bucket/key', StreamMode::READ_ONLY)
            ->once()
            ->andReturn(fopen('php://memory', 'rb'));

        $this->s3StreamFactory->createReadOnlyStream('bucket', 'key');
    }

    public function testCreateWriteOnlyStream(): void
    {
        $this->s3StreamFactory
            ->shouldReceive('doFopen')
            ->with('s3://bucket/key', StreamMode::WRITE_ONLY)
            ->once()
            ->andReturn(fopen('php://memory', 'wb'));

        $this->s3StreamFactory->createWriteOnlyStream('bucket', 'key');
    }
}
