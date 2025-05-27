<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex;

use App\Domain\WooIndex\WooIndexSitemap;
use App\Domain\WooIndex\WooIndexSitemapRepository;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexSitemapsStory;
use Carbon\CarbonImmutable;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Persistence\Proxy;

final class WooIndexSitemapRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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

        /** @var list<WooIndexSitemap&Proxy<WooIndexSitemap>> $finishedSitemaps */
        $finishedSitemaps = WooIndexSitemapsStory::getPool('finishedSitemaps');

        $this->assertNotNull($result);
        $this->assertSame($finishedSitemaps[0]->_real()->getId()->toRfc4122(), $result->getId()->toRfc4122());
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

        /** @var list<WooIndexSitemap&Proxy<WooIndexSitemap>> $finishedSitemaps */
        $finishedSitemaps = WooIndexSitemapsStory::getPool('finishedSitemaps');

        $this->assertNotContains($finishedSitemaps[0]->_real(), $result, 'FinishedSitemap #0 is not returned');
        $this->assertNotContains($finishedSitemaps[1]->_real(), $result, 'FinishedSitemap #1 is not returned');
        $this->assertNotContains($finishedSitemaps[2]->_real(), $result, 'FinishedSitemap #2 is not returned');
        $this->assertContains($finishedSitemaps[3]->_real(), $result, 'FinishedSitemap #3 is returned');
        $this->assertContains($finishedSitemaps[4]->_real(), $result, 'FinishedSitemap #4 is returned');
        $this->assertContains($finishedSitemaps[5]->_real(), $result, 'FinishedSitemap #5 is returned');
        $this->assertContains($finishedSitemaps[6]->_real(), $result, 'FinishedSitemap #6 is returned');
        $this->assertContains($finishedSitemaps[7]->_real(), $result, 'FinishedSitemap #7 is returned');
        $this->assertContains($finishedSitemaps[8]->_real(), $result, 'FinishedSitemap #8 is returned');
        $this->assertContains($finishedSitemaps[9]->_real(), $result, 'FinishedSitemap #9 is returned');

        /** @var list<WooIndexSitemap&Proxy<WooIndexSitemap>> $unfinishedSitemaps */
        $unfinishedSitemaps = WooIndexSitemapsStory::getPool('unfinishedSitemaps');

        $this->assertNotContains($unfinishedSitemaps[0]->_real(), $result, 'UnfinishedSitemap #0 is not returned');
        $this->assertNotContains($unfinishedSitemaps[1]->_real(), $result, 'UnfinishedSitemap #1 is not returned');
        $this->assertNotContains($unfinishedSitemaps[2]->_real(), $result, 'UnfinishedSitemap #2 is not returned');
        $this->assertNotContains($unfinishedSitemaps[3]->_real(), $result, 'UnfinishedSitemap #3 is returned');
        $this->assertNotContains($unfinishedSitemaps[4]->_real(), $result, 'UnfinishedSitemap #4 is returned');
        $this->assertContains($unfinishedSitemaps[5]->_real(), $result, 'UnfinishedSitemap #5 is returned');
        $this->assertContains($unfinishedSitemaps[6]->_real(), $result, 'UnfinishedSitemap #6 is returned');
        $this->assertContains($unfinishedSitemaps[7]->_real(), $result, 'UnfinishedSitemap #7 is returned');
        $this->assertContains($unfinishedSitemaps[8]->_real(), $result, 'UnfinishedSitemap #8 is returned');
        $this->assertContains($unfinishedSitemaps[9]->_real(), $result, 'UnfinishedSitemap #9 is returned');
    }

    public function testGetSitemapsForCleanupWithoutAnyRecords(): void
    {
        $treshold = 3;
        $date = CarbonImmutable::now()->subDays(5);

        $result = $this->wooIndexSitemapRepository->getSitemapsForCleanup($treshold, $date);

        $this->assertSame([], iterator_to_array($result, false));
    }
}
