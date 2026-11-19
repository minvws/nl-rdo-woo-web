<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\ViewModel\DossierFile;
use Shared\Domain\Publication\Dossier\ViewModel\Page;
use Shared\Domain\Publication\Dossier\ViewModel\SpreadsheetPreview;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;
use Shared\Tests\Unit\UnitTestCase;

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
            $spreadsheet = new SpreadsheetPreview(new SpreadsheetTable(['a'], [['b']]), true),
            ...$pages,
        );

        self::assertEquals($type, $dossierFile->type);
        self::assertEquals($size, $dossierFile->size);
        self::assertEquals($hasPages, $dossierFile->hasPages);
        self::assertEquals($downloadUrl, $dossierFile->downloadUrl);
        self::assertEquals($spreadsheet, $dossierFile->spreadsheet);
        self::assertEquals($pages, $dossierFile->pages);
    }
}
