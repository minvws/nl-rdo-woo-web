<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\BatchDownload\Archiver;

use Mockery;
use Psr\Http\Message\StreamInterface;
use Shared\Domain\Publication\BatchDownload\Archiver\ZipStreamFactory;
use Shared\Tests\Unit\UnitTestCase;
use ZipStream\ZipStream;

final class ZipStreamFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new ZipStreamFactory();

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('isReadable')->andReturnTrue();
        $stream->shouldReceive('isWritable')->andReturnTrue();

        $result = $factory->create($stream);

        $this->assertInstanceOf(ZipStream::class, $result);
    }

    public function testForStreamingArchive(): void
    {
        $factory = new ZipStreamFactory();

        $stream = Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('isReadable')->andReturnTrue();
        $stream->shouldReceive('isWritable')->andReturnTrue();

        $result = $factory->forStreamingArchive('base-name');

        $this->assertInstanceOf(ZipStream::class, $result);
    }
}
