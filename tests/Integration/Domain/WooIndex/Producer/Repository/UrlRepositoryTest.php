<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex\Producer\Repository;

use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\WooIndex\Producer\Repository\RawUrlDto;
use App\Domain\WooIndex\Producer\Repository\UrlRepository;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use App\Tests\Story\WooIndexWooDecisionStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\WithStory;

final class UrlRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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
