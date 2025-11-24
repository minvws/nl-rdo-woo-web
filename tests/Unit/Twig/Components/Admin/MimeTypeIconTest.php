<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Twig\Components\Admin;

use Shared\Domain\Upload\FileType\FileType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\Twig\Components\Admin\MimeTypeIcon;

final class MimeTypeIconTest extends UnitTestCase
{
    private MimeTypeIcon $mimeTypeIcon;

    protected function setUp(): void
    {
        $this->mimeTypeIcon = new MimeTypeIcon();

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

        self::assertEquals(
            'file-unknown',
            $this->mimeTypeIcon->getIconName(),
        );
    }

    public function testGetIconNameReturnsPdf(): void
    {
        $this->mimeTypeIcon->mimeType = FileType::PDF->getMimeTypes()[0];

        self::assertEquals(
            'file-pdf',
            $this->mimeTypeIcon->getIconName(),
        );
    }
}
