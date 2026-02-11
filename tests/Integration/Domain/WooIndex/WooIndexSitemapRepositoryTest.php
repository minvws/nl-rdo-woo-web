<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\WooIndex;

use Carbon\CarbonImmutable;
use Shared\Domain\WooIndex\WooIndexSitemap;
use Shared\Domain\WooIndex\WooIndexSitemapRepository;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexSitemapsStory;
use Zenstruck\Foundry\Attribute\WithStory;

use function iterator_to_array;

final class WooIndexSitemapRepositoryTest extends SharedWebTestCase
{
    private WooIndexSitemapRepository $wooIndexSitemapRepository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->wooIndexSitemapRepository = self::getContainer()->get(WooIndexSitemapRepository::class);
    }

    #[WithStory(WooIndexSitemapsStory::class)]
    public function testLastFinished(): void
    {
        $result = $this->wooIndexSitemapRepository->lastFinished();

        /** @var list<WooIndexSitemap> $finishedSitemaps */
        $finishedSitemaps = WooIndexSitemapsStory::getPool('finishedSitemaps');

        $this->assertNotNull($result);
        $this->assertSame($finishedSitemaps[0]->getId()->toRfc4122(), $result->getId()->toRfc4122());
    }

    public function testLastFinishedReturnsNullOnNoRecords(): void
    {
        $result = $this->wooIndexSitemapRepository->lastFinished();

        $this->assertNull($result);
    }

    #[WithStory(WooIndexSitemapsStory::class)]
    public function testGetSitemapsForCleanup(): void
    {
        $this->setTestNow($now = WooIndexSitemapsStory::now());

        $treshold = 3;
        $date = $now->subDays(5);

        $result = iterator_to_array(
            $this->wooIndexSitemapRepository->getSitemapsForCleanup($treshold, $date),
            preserve_keys: false,
        );

        /** @var list<WooIndexSitemap> $finishedSitemaps */
        $finishedSitemaps = WooIndexSitemapsStory::getPool('finishedSitemaps');

        $this->assertNotContains($finishedSitemaps[0], $result, 'FinishedSitemap #0 is not returned');
        $this->assertNotContains($finishedSitemaps[1], $result, 'FinishedSitemap #1 is not returned');
        $this->assertNotContains($finishedSitemaps[2], $result, 'FinishedSitemap #2 is not returned');
        $this->assertContains($finishedSitemaps[3], $result, 'FinishedSitemap #3 is returned');
        $this->assertContains($finishedSitemaps[4], $result, 'FinishedSitemap #4 is returned');
        $this->assertContains($finishedSitemaps[5], $result, 'FinishedSitemap #5 is returned');
        $this->assertContains($finishedSitemaps[6], $result, 'FinishedSitemap #6 is returned');
        $this->assertContains($finishedSitemaps[7], $result, 'FinishedSitemap #7 is returned');
        $this->assertContains($finishedSitemaps[8], $result, 'FinishedSitemap #8 is returned');
        $this->assertContains($finishedSitemaps[9], $result, 'FinishedSitemap #9 is returned');

        /** @var list<WooIndexSitemap> $unfinishedSitemaps */
        $unfinishedSitemaps = WooIndexSitemapsStory::getPool('unfinishedSitemaps');

        $this->assertNotContains($unfinishedSitemaps[0], $result, 'UnfinishedSitemap #0 is not returned');
        $this->assertNotContains($unfinishedSitemaps[1], $result, 'UnfinishedSitemap #1 is not returned');
        $this->assertNotContains($unfinishedSitemaps[2], $result, 'UnfinishedSitemap #2 is not returned');
        $this->assertNotContains($unfinishedSitemaps[3], $result, 'UnfinishedSitemap #3 is returned');
        $this->assertNotContains($unfinishedSitemaps[4], $result, 'UnfinishedSitemap #4 is returned');
        $this->assertContains($unfinishedSitemaps[5], $result, 'UnfinishedSitemap #5 is returned');
        $this->assertContains($unfinishedSitemaps[6], $result, 'UnfinishedSitemap #6 is returned');
        $this->assertContains($unfinishedSitemaps[7], $result, 'UnfinishedSitemap #7 is returned');
        $this->assertContains($unfinishedSitemaps[8], $result, 'UnfinishedSitemap #8 is returned');
        $this->assertContains($unfinishedSitemaps[9], $result, 'UnfinishedSitemap #9 is returned');
    }

    public function testGetSitemapsForCleanupWithoutAnyRecords(): void
    {
        $treshold = 3;
        $date = CarbonImmutable::now()->subDays(5);

        $result = $this->wooIndexSitemapRepository->getSitemapsForCleanup($treshold, $date);

        $this->assertSame([], iterator_to_array($result, false));
    }
}
