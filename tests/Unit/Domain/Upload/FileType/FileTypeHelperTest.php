<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload\FileType;

use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Mime\MimeTypes;

final class FileTypeHelperTest extends UnitTestCase
{
    private FileTypeHelper $helper;

    public function setUp(): void
    {
        $this->helper = new FileTypeHelper(new MimeTypes());

        parent::setUp();
    }

    public function testGetFileTypeReturnsNullForUnknownMimeType(): void
    {
        self::assertNull($this->helper->getFileType('foo/bar'));
    }

    public function testGetFileTypeForKnownMimeTypeReturnsFileType(): void
    {
        self::assertEquals(
            FileType::DOC,
            $this->helper->getFileType('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        );
    }

    public function testGetMimeTypes(): void
    {
        self::assertEquals(
            [
                'application/zip',
                'application/x-zip',
                'application/x-zip-compressed',
                'application/x-7z-compressed',
                'text/plain',
                'application/rdf+xml',
                'text/rdf',
            ],
            $this->helper->getMimeTypes(FileType::ZIP, FileType::TXT),
        );
    }

    public function testGetMimeTypesByUploadGroup(): void
    {
        $this->assertMatchesObjectSnapshot(
            $this->helper->getMimeTypesByUploadGroup(UploadGroupId::DISPOSITION_DOCUMENTS),
        );
    }

    public function testGetExtensionsByUploadGroup(): void
    {
        $this->assertMatchesObjectSnapshot(
            $this->helper->getExtensionsByUploadGroup(UploadGroupId::DISPOSITION_DOCUMENTS)
        );
    }

    public function testGetTypeNamesByUploadGroup(): void
    {
        $this->assertMatchesObjectSnapshot(
            $this->helper->getTypeNamesByUploadGroup(UploadGroupId::DISPOSITION_DOCUMENTS)
        );
    }
}
