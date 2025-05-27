<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\BatchDownload\Archiver;

use App\Domain\Publication\BatchDownload\Archiver\ArchiveNamer;
use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class ArchiveNamerTest extends UnitTestCase
{
    public function testGetArchiveName(): void
    {
        $basename = 'my-base-name';

        $batchDownload = \Mockery::mock(BatchDownload::class);
        $batchDownload->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1f009841-b03a-6a12-b294-b1a98c0dda11'));

        $archiveNamer = new ArchiveNamer();

        $result = $archiveNamer->getArchiveName($basename, $batchDownload);

        $this->assertSame('my-base-name-1f009841-b03a-6a12-b294-b1a98c0dda11.zip', $result);
    }

    public function testGetArchiveNameForStream(): void
    {
        $basename = 'my-base~\name';

        $archiveNamer = new ArchiveNamer();

        $result = $archiveNamer->getArchiveNameForStream($basename);

        $this->assertSame('my-base_name.zip', $result);
    }
}
