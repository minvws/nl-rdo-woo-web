<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\DossierFile;
use App\Domain\Publication\Dossier\ViewModel\Page;
use App\Tests\Unit\UnitTestCase;

final class DossierFileTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $pages = [
            new Page(1, 'thumb-url', 'view-url'),
            new Page(2, null, null),
        ];

        $dossierFile = new DossierFile(
            $type = 'pdf',
            $size = 456,
            $hasPages = true,
            $downloadUrl = '/foo/bar',
            ...$pages
        );

        self::assertEquals($type, $dossierFile->type);
        self::assertEquals($size, $dossierFile->size);
        self::assertEquals($hasPages, $dossierFile->hasPages);
        self::assertEquals($downloadUrl, $dossierFile->downloadUrl);
        self::assertEquals($pages, $dossierFile->pages);
    }
}
