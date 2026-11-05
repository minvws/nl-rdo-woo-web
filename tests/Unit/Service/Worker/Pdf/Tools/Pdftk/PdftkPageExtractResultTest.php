<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftk;

use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use Shared\Tests\Unit\UnitTestCase;

final class PdftkPageExtractResultTest extends UnitTestCase
{
    public function testIsSuccessfulAndIsFailed(): void
    {
        $resultOne = new PdftkPageExtractResult(0, [], null, 1, $this->getFaker()->sha256());
        $resultTwo = new PdftkPageExtractResult(1, [], $this->getFaker()->sentence(), 1, null);

        $this->assertTrue($resultOne->isSuccessful());
        $this->assertFalse($resultOne->isFailed());

        $this->assertFalse($resultTwo->isSuccessful());
        $this->assertTrue($resultTwo->isFailed());
    }
}
