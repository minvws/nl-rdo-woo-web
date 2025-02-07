<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf\Tools\Pdftk;

use App\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use App\Tests\Unit\UnitTestCase;

final class PdftkPageCountResultTest extends UnitTestCase
{
    public function testIsSuccessfulAndIsFailed(): void
    {
        $resultOne = new PdftkPageCountResult(0, [], null, 'source.pdf', 1);
        $resultTwo = new PdftkPageCountResult(1, [], null, 'source.pdf', 1);

        $this->assertTrue($resultOne->isSuccessful());
        $this->assertFalse($resultOne->isFailed());

        $this->assertFalse($resultTwo->isSuccessful());
        $this->assertTrue($resultTwo->isFailed());
    }
}
