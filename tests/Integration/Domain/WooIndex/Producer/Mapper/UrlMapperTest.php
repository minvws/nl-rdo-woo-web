<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex\Producer\Mapper;

use App\Domain\WooIndex\Producer\Mapper\UrlMapper;
use App\Domain\WooIndex\Producer\Repository\UrlRepository;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use App\Tests\Story\WooIndexWooDecisionStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;

final class UrlMapperTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private UrlRepository $urlRepository;

    private UrlMapper $urlMapper;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->urlRepository = self::getContainer()->get(UrlRepository::class);

        $this->urlMapper = self::getContainer()->get(UrlMapper::class);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testFromEntityWithDocument(): void
    {
        $rawUrls = $this->urlRepository->getPublishedDocuments();

        $url = $this->urlMapper->fromRawUrl($this->getFirstItem($rawUrls));

        $this->assertMatchesObjectSnapshot($url);
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testFromEntityWithMainDocument(): void
    {
        $rawUrls = $this->urlRepository->getPublishedMainDocuments();

        $url = $this->urlMapper->fromRawUrl($this->getFirstItem($rawUrls));

        $this->assertMatchesObjectSnapshot($url);
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testFromEntityWithAttachment(): void
    {
        $rawUrls = $this->urlRepository->getPublishedAttachments();

        $url = $this->urlMapper->fromRawUrl($this->getFirstItem($rawUrls));

        $this->assertMatchesObjectSnapshot($url);
    }

    /**
     * @template TKey of array-key
     * @template TValue
     *
     * @param iterable<TKey,TValue> $i
     *
     * @return TValue
     */
    private function getFirstItem(iterable $i)
    {
        foreach ($i as $v) {
            return $v;
        }

        $this->fail('getFirstItem() failed. Expected at least 1 value in iterable, non found.');
    }
}
