<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig\Components\Admin;

use App\Domain\Upload\FileType\FileType;
use App\Twig\Components\Admin\MimeTypeIcon;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class MimeTypeIconTest extends MockeryTestCase
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
