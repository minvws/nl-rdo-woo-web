<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ArchiveExtractor\Factory;

use App\Domain\ArchiveExtractor\Factory\SevenZipArchiveFactory;
use App\Tests\Unit\UnitTestCase;
use Archive7z\Archive7z;

final class SevenZipArchiveFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new SevenZipArchiveFactory();

        $archive = $factory->create('archive.7z');

        $this->assertInstanceOf(Archive7z::class, $archive);
    }
}
