<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\Extractor;

use App\Domain\Upload\Extractor\ExtractorFinderFactory;
use App\Tests\Unit\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\Finder\Finder;

final class ExtractorFinderFactoryTest extends UnitTestCase
{
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();
    }

    public function testCreate(): void
    {
        $dir = vfsStream::newDirectory($dir = 'directory')->at($this->root);

        $finder = (new ExtractorFinderFactory())->create($dir->url());

        $this->assertInstanceOf(Finder::class, $finder);
    }
}
