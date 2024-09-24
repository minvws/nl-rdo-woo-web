<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\ArchiveExtractor\Factory;

use App\Domain\ArchiveExtractor\Factory\ZipArchiveFactory;
use App\Tests\Unit\UnitTestCase;

final class ZipArchiveFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $factory = new ZipArchiveFactory();

        $archive = $factory->create();

        $this->assertInstanceOf(\ZipArchive::class, $archive);
    }
}
