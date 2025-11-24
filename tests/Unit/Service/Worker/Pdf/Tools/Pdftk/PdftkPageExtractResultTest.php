<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftk;

use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageExtractResult;
use Shared\Tests\Unit\UnitTestCase;

final class PdftkPageExtractResultTest extends UnitTestCase
{
    public function testIsSuccessfulAndIsFailed(): void
    {
        $resultOne = new PdftkPageExtractResult(0, [], null, 'source.pdf', 1, 'target.pdf');
        $resultTwo = new PdftkPageExtractResult(1, [], null, 'source.pdf', 1, 'target.pdf');

        $this->assertTrue($resultOne->isSuccessful());
        $this->assertFalse($resultOne->isFailed());

        $this->assertFalse($resultTwo->isSuccessful());
        $this->assertTrue($resultTwo->isFailed());
    }
}
