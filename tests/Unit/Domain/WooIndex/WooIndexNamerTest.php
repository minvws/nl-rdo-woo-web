<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex;

use App\Domain\WooIndex\WooIndexNamer;
use App\Domain\WooIndex\WooIndexSitemap;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

final class WooIndexNamerTest extends UnitTestCase
{
    private WooIndexNamer&MockInterface $wooIndexNamer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooIndexNamer = \Mockery::mock(WooIndexNamer::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testGetStorageSubpat(): void
    {
        $wooIndexSitemap = $this->getWooIndexSitemap(Uuid::fromRfc4122('1efe88cf-1e86-6a86-a022-dfa43a74a2ab'));

        $result = $this->wooIndexNamer->getStorageSubpath($wooIndexSitemap);

        $this->assertSame('1efe88cf-1e86-6a86-a022-dfa43a74a2ab/', $result);
    }

    #[DataProvider('getSitemapNameData')]
    public function testGetSitemapName(int $input, string $expected): void
    {
        $this->assertSame($expected, $this->wooIndexNamer->getSitemapName($input));
    }

    /**
     * @return array<string,array{input:int,expected:string}>
     */
    public static function getSitemapNameData(): array
    {
        return [
            'single digit' => [
                'input' => 1,
                'expected' => 'sitemap-00001.xml',
            ],
            'double digit' => [
                'input' => 33,
                'expected' => 'sitemap-00033.xml',
            ],
            'quadrupple digit' => [
                'input' => 1337,
                'expected' => 'sitemap-01337.xml',
            ],
            'quintuple digit' => [
                'input' => 12_456,
                'expected' => 'sitemap-12456.xml',
            ],
            'sextuple digit' => [
                'input' => 169_069,
                'expected' => 'sitemap-169069.xml',
            ],
        ];
    }

    public function getSitemapIndexName(): void
    {
        $this->assertSame('sitemap-index.xml', $this->wooIndexNamer->getSitemapIndexName());
    }

    private function getWooIndexSitemap(Uuid $uuid): WooIndexSitemap
    {
        $wooIndexSitemap = new WooIndexSitemap();

        $reflection = new \ReflectionClass($wooIndexSitemap);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($wooIndexSitemap, $uuid);

        return $wooIndexSitemap;
    }
}
