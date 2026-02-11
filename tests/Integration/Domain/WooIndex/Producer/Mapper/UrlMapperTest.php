<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\WooIndex\Producer\Mapper;

use Shared\Domain\WooIndex\Producer\Mapper\UrlMapper;
use Shared\Domain\WooIndex\Producer\Repository\UrlRepository;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexAnnualReportStory;
use Shared\Tests\Story\WooIndexWooDecisionStory;
use Zenstruck\Foundry\Attribute\WithStory;

use function iterator_to_array;

final class UrlMapperTest extends SharedWebTestCase
{
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
        $i = iterator_to_array($i, false); // prevents segmentation fault on xdebug.mode=develop

        foreach ($i as $v) {
            return $v;
        }

        $this->fail('getFirstItem() failed. Expected at least 1 value in iterable, non found.');
    }
}
