<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\Archiver;

use App\Domain\Publication\BatchDownload\Archiver\ZipStreamFactory;
use App\Tests\Unit\UnitTestCase;
use Psr\Http\Message\StreamInterface;
use ZipStream\ZipStream;

final class ZipStreamFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new ZipStreamFactory();

        $stream = \Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('isReadable')->andReturnTrue();
        $stream->shouldReceive('isWritable')->andReturnTrue();

        $result = $factory->create($stream);

        $this->assertInstanceOf(ZipStream::class, $result);
    }

    public function testForStreamingArchive(): void
    {
        $factory = new ZipStreamFactory();

        $stream = \Mockery::mock(StreamInterface::class);
        $stream->shouldReceive('isReadable')->andReturnTrue();
        $stream->shouldReceive('isWritable')->andReturnTrue();

        $result = $factory->forStreamingArchive('base-name');

        $this->assertInstanceOf(ZipStream::class, $result);
    }
}
