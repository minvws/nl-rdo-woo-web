<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\WooIndex\Producer\Repository;

use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\WooIndex\Producer\Repository\RawUrlDto;
use Shared\Domain\WooIndex\Producer\Repository\UrlRepository;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexAnnualReportStory;
use Shared\Tests\Story\WooIndexWooDecisionStory;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\WithStory;

final class UrlRepositoryTest extends SharedWebTestCase
{
    private UrlRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(UrlRepository::class);
        Assert::isInstanceOf($repository, UrlRepository::class);

        $this->repository = $repository;
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testGetPublishedDocuments(): void
    {
        $results = $this->repository->getPublishedDocuments();

        $resultAsArray = iterator_to_array($results, false);

        $this->assertCount(20, $resultAsArray);
        foreach ($resultAsArray as $result) {
            $this->assertInstanceOf(RawUrlDto::class, $result);
            $this->assertSame(DossierFileType::DOCUMENT, $result->source);
        }
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testGetPublishedAttachments(): void
    {
        $results = $this->repository->getPublishedAttachments();

        $resultAsArray = iterator_to_array($results, false);

        $this->assertCount(3, $resultAsArray);
        foreach ($resultAsArray as $result) {
            $this->assertInstanceOf(RawUrlDto::class, $result);
            $this->assertSame(DossierFileType::ATTACHMENT, $result->source);
        }
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testGetPublishedMainDocuments(): void
    {
        $results = $this->repository->getPublishedMainDocuments();

        $resultAsArray = iterator_to_array($results, false);

        $this->assertCount(2, $resultAsArray);
        foreach ($resultAsArray as $result) {
            $this->assertInstanceOf(RawUrlDto::class, $result);
            $this->assertSame(DossierFileType::MAIN_DOCUMENT, $result->source);
        }
    }
}
