<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\ArchiveExtractor\Factory;

use Archive7z\Archive7z;
use Shared\Domain\ArchiveExtractor\Factory\SevenZipArchiveFactory;
use Shared\Tests\Unit\UnitTestCase;

final class SevenZipArchiveFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new SevenZipArchiveFactory();

        $archive = $factory->create('archive.7z');

        $this->assertInstanceOf(Archive7z::class, $archive);
    }
}
