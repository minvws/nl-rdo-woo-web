<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Domain\Upload\UploadedFile;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Mime\MimeTypesInterface;

final class FileTypeHelperTest extends UnitTestCase
{
    private MimeTypesInterface&MockInterface $mimeTypes;

    protected function setUp(): void
    {
        $this->mimeTypes = \Mockery::mock(MimeTypesInterface::class);
    }

    public function testFilenameOfTypeReturnsTrue(): void
    {
        $pathname = 'path/to/file.pdf';

        $this->mimeTypes
            ->shouldReceive('guessMimeType')
            ->once()
            ->with($pathname)
            ->andReturn('application/pdf');

        $result = (new FileTypeHelper($this->mimeTypes))->pathnameOfType($pathname, FileType::PDF);

        $this->assertTrue($result, 'File is of type PDF');
    }

    public function testFilenameOfTypeReturnsFalse(): void
    {
        $pathname = 'path/to/file.pdf';

        $this->mimeTypes
            ->shouldReceive('guessMimeType')
            ->once()
            ->with($pathname)
            ->andReturn('application/pdf');

        $result = (new FileTypeHelper($this->mimeTypes))->pathnameOfType($pathname, FileType::XLS);

        $this->assertFalse($result, 'File is not of type XLS');
    }

    public function testFilenameOfTypeReturnsTrueWithMultipleTypesGiven(): void
    {
        $pathname = 'path/to/file.pdf';

        $this->mimeTypes
            ->shouldReceive('guessMimeType')
            ->once()
            ->with($pathname)
            ->andReturn('application/pdf');

        $result = (new FileTypeHelper($this->mimeTypes))->pathnameOfType($pathname, FileType::XLS, FileType::PDF);

        $this->asserTTrue($result, 'File is of type PDF');
    }

    public function testFilenameOfTypeReturnsFalseIfMimeTypeCannotBeDetermined(): void
    {
        $pathname = 'path/to/file.pdf';

        $this->mimeTypes
            ->shouldReceive('guessMimeType')
            ->once()
            ->with($pathname)
            ->andReturnNull();

        $result = (new FileTypeHelper($this->mimeTypes))->pathnameOfType($pathname, FileType::PDF);

        $this->assertFalse($result, 'File is not of type PDF');
    }

    public function testFileOfTypeCallsPathnameOfType(): void
    {
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getPathname')->once()->andReturn($pathname = 'path/to/file.pdf');

        /** @var FileTypeHelper&MockInterface $helper */
        $helper = \Mockery::mock(FileTypeHelper::class, [$this->mimeTypes])->makePartial();

        $types = [FileType::PDF, FileType::XLS];

        $helper
            ->shouldReceive('pathnameOfType')
            ->once()
            ->with($pathname, ...$types)
            ->andReturnTrue();

        $result = $helper->fileOfType($file, ...$types);

        $this->assertTrue($result, 'File is of type PDF');
    }
}
