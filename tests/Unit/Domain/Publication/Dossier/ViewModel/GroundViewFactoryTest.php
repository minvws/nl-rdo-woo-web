<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use Shared\Tests\Unit\UnitTestCase;

final class GroundViewFactoryTest extends UnitTestCase
{
    public function testMakeWithWooCitation(): void
    {
        $citation = $this->getFaker()->wooGround();

        $result = new GroundViewFactory()->make($citation);

        $this->assertSame($citation, $result->citation);
        $this->assertSame(Citation::$wooCitations[$citation], $result->classification);
        $this->assertSame('https://wetten.overheid.nl/BWBR0045754/#Hoofdstuk5', $result->url);
    }

    public function testMakeWithWobCitation(): void
    {
        $citation = $this->getFaker()->wobGround();

        $result = new GroundViewFactory()->make($citation);

        $this->assertSame($citation, $result->citation);
        $this->assertSame(Citation::$wobCitations[$citation], $result->classification);
        $this->assertSame('https://wetten.overheid.nl/BWBR0005252/#HoofdstukV', $result->url);
    }

    public function testMakeCollection(): void
    {
        $wooCitation = $this->getFaker()->wooGround();
        $wobCitation = $this->getFaker()->wobGround();

        $result = new GroundViewFactory()->makeCollection([$wooCitation, $wobCitation]);

        $this->assertCount(2, $result);
        $this->assertSame($wooCitation, $result[0]->citation);
        $this->assertSame(Citation::$wooCitations[$wooCitation], $result[0]->classification);
        $this->assertSame($wobCitation, $result[1]->citation);
        $this->assertSame(Citation::$wobCitations[$wobCitation], $result[1]->classification);
    }

    public function testMakeOptions(): void
    {
        $result = new GroundViewFactory()->makeOptions();

        $this->assertMatchesJsonSnapshot($result);
    }
}
