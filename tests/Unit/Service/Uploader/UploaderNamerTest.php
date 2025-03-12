<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Uploader;

use App\Service\Uploader\UploaderNamer;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[Group('uploader')]
class UploaderNamerTest extends UnitTestCase
{
    private UploaderNamer&MockInterface $uploaderNamer;
    private RequestStack&MockInterface $requestStack;
    private FilesystemFile&MockInterface $file;
    private Request&MockInterface $request;

    protected function setUp(): void
    {
        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->uploaderNamer = \Mockery::mock(UploaderNamer::class, [$this->requestStack])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->file = \Mockery::mock(FilesystemFile::class);
        $this->request = \Mockery::mock(Request::class);
    }

    public function testName(): void
    {
        $this->request->shouldReceive('get')->with('groupId')->andReturn(UploadGroupId::ATTACHMENTS->value);

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);

        $this->file->shouldReceive('getClientOriginalName')->andReturn('foo.bar');

        $this->uploaderNamer->shouldReceive('getUniqid')->andReturn('uniqid_value');

        $name = $this->uploaderNamer->name($this->file);

        self::assertSame('attachments/uniqid_value_foo.bar', $name);
    }

    public function testNameWithInvalidGroupId(): void
    {
        $this->request->shouldReceive('get')->with('groupId')->andReturn('INVALID');

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);

        $this->file->shouldReceive('getClientOriginalName')->andReturn('foo.bar');

        self::expectException(\ValueError::class);

        $this->uploaderNamer->name($this->file);
    }

    public function testNameIsRevertibleWithGetOriginalName(): void
    {
        $this->request->shouldReceive('get')->with('groupId')->andReturn(UploadGroupId::ATTACHMENTS->value);

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);

        $this->file->shouldReceive('getClientOriginalName')->andReturn($originalName = 'foo.bar');

        $name = $this->uploaderNamer->name($this->file);

        self::assertNotEquals($originalName, $name);
        self::assertEquals($originalName, UploaderNamer::getOriginalName($name));
    }
}
