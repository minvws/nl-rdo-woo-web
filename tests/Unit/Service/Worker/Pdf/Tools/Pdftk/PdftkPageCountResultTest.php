<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftk;

use Shared\Service\Worker\Pdf\Tools\Pdftk\PdftkPageCountResult;
use Shared\Tests\Unit\UnitTestCase;

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
