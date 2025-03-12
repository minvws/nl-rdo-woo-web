<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\WooIndex\Producer;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\WooIndex\Producer\UrlMapper;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexAnnualReportStory;
use App\Tests\Story\WooIndexWooDecisionStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Persistence\Proxy;

final class UrlMapperTest extends KernelTestCase
{
    use IntegrationTestTrait;

    private UrlMapper $urlMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlMapper = self::getContainer()->get(UrlMapper::class);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testFromEntityWithDocument(): void
    {
        /** @var non-empty-list<Proxy<Document>> $documents */
        $documents = WooIndexWooDecisionStory::getPool('documents');

        $url = $this->urlMapper->fromEntity($documents[0]->_real());

        $this->assertMatchesObjectSnapshot($url);
    }

    #[WithStory(WooIndexAnnualReportStory::class)]
    public function testFromEntityWithMainDocument(): void
    {
        /** @var Proxy<AnnualReportMainDocument> $annualReportMainDocument */
        $annualReportMainDocument = WooIndexAnnualReportStory::get('mainDocument');

        $url = $this->urlMapper->fromEntity($annualReportMainDocument->_real());

        $this->assertMatchesObjectSnapshot($url);
    }

    public function testFromEntityWithAttachment(): void
    {
        /** @var non-empty-list<Proxy<AnnualReportAttachment>> $annualReportAttachments */
        $annualReportAttachments = WooIndexAnnualReportStory::getPool('attachments');

        $url = $this->urlMapper->fromEntity($annualReportAttachments[0]->_real());

        $this->assertMatchesObjectSnapshot($url);
    }
}
