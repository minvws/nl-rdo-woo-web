<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Upload\FileType;

use Shared\Domain\Upload\FileType\MimeTypeHelperResult;
use Shared\Tests\Unit\UnitTestCase;

class MimeTypeHelperResultTest extends UnitTestCase
{
    public function testEnum(): void
    {
        $this->assertMatchesObjectSnapshot(MimeTypeHelperResult::cases());
    }
}
