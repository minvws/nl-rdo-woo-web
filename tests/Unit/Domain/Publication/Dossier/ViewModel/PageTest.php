<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\ViewModel\Page;
use App\Tests\Unit\UnitTestCase;

final class PageTest extends UnitTestCase
{
    public function testGetters(): void
    {
        $page = new Page(
            $pageNr = 1,
            $thumbUrl = 'thumb-url',
            $viewUrl = 'view-url',
        );

        self::assertEquals($pageNr, $page->pageNr);
        self::assertEquals($thumbUrl, $page->thumbnailUrl);
        self::assertEquals($viewUrl, $page->viewUrl);
    }
}
