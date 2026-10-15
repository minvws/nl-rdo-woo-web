<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\WooIndex;

use Shared\Domain\WooIndex\WooIndexSitemap;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

final class WooIndexSitemapTest extends UnitTestCase
{
    public function testDefaultId(): void
    {
        $wooIndexSitemap = new WooIndexSitemap();

        self::assertInstanceOf(WooUuid::class, $wooIndexSitemap->getId());
    }

    public function testGivenId(): void
    {
        $id = Uuid::fromString($this->getFaker()->uuid());
        $wooIndexSitemap = new WooIndexSitemap($id);

        self::assertSame($id, $wooIndexSitemap->getId());
    }
}
