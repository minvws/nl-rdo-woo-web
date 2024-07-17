<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Components\Admin;

use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Twig\Components\Admin\MimeTypeIcon;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class MimeTypeIconTest extends MockeryTestCase
{
    private MimeTypeIcon $mimeTypeIcon;
    private FileTypeHelper&MockInterface $fileTypeHelper;

    public function setUp(): void
    {
        $this->fileTypeHelper = \Mockery::mock(FileTypeHelper::class);

        $this->mimeTypeIcon = new MimeTypeIcon(
            $this->fileTypeHelper,
        );

        parent::setUp();
    }

    public function testGetIconNameReturnsUnknownForEmptyMimetype(): void
    {
        $this->mimeTypeIcon->mimeType = null;
        self::assertEquals(
            'file-unknown',
            $this->mimeTypeIcon->getIconName(),
        );
    }

    public function testGetIconNameReturnsUnknownWhenFileTypeCannotBeResolved(): void
    {
        $this->mimeTypeIcon->mimeType = 'foo/bar';
        $this->fileTypeHelper->expects('getFileType')->with('foo/bar')->andReturnNull();

        self::assertEquals(
            'file-unknown',
            $this->mimeTypeIcon->getIconName(),
        );
    }

    public function testGetIconNameReturnsPdf(): void
    {
        $this->mimeTypeIcon->mimeType = 'foo/bar';
        $this->fileTypeHelper->expects('getFileType')->with('foo/bar')->andReturn(FileType::PDF);

        self::assertEquals(
            'file-pdf',
            $this->mimeTypeIcon->getIconName(),
        );
    }
}
