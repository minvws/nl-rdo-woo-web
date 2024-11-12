<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Uploader;

use App\Service\Uploader\UploaderNamer;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\RequestStack;

#[Group('uploader')]
class UploaderNamerTest extends UnitTestCase
{
    private UploaderNamer $uploaderNamer;
    private RequestStack&MockInterface $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->uploaderNamer = new UploaderNamer($this->requestStack);
    }

    public function testNameIsRevertibleWithGetOriginalName(): void
    {
        $this->requestStack->expects('getCurrentRequest')->andReturnNull();

        $file = \Mockery::mock(FilesystemFile::class);
        $file->shouldReceive('getClientOriginalName')->andReturn($originalName = 'foo.bar');

        $name = $this->uploaderNamer->name($file);

        self::assertNotEquals($name, $originalName);
        self::assertEquals($originalName, UploaderNamer::getOriginalName($name));
    }
}
