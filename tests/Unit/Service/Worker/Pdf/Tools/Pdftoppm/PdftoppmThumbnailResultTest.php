<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Worker\Pdf\Tools\Pdftoppm;

use Shared\Service\Worker\Pdf\Tools\Pdftoppm\PdftoppmThumbnailResult;
use Shared\Tests\Unit\UnitTestCase;

final class PdftoppmThumbnailResultTest extends UnitTestCase
{
    public function testIsSuccessfulAndIsFailed(): void
    {
        $resultOne = new PdftoppmThumbnailResult(0, [], null, 'source.pdf', 'target.png');
        $resultTwo = new PdftoppmThumbnailResult(1, [], null, 'source.pdf', 'target.png');

        $this->assertTrue($resultOne->isSuccessful());
        $this->assertFalse($resultOne->isFailed());

        $this->assertFalse($resultTwo->isSuccessful());
        $this->assertTrue($resultTwo->isFailed());
    }
}
