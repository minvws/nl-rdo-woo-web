<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\MainDocument;

use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexAnnualReportStory;
use Shared\Tests\Story\WooIndexCovenantStory;
use Shared\Tests\Story\WooIndexWooDecisionStory;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\WithStory;

use function iterator_to_array;

final class MainDocumentRepositoryTest extends SharedWebTestCase
{
    #[WithStory(WooIndexWooDecisionStory::class)]
    #[WithStory(WooIndexAnnualReportStory::class)]
    #[WithStory(WooIndexCovenantStory::class)]
    public function testGetPublishedMainDocumentsIterable(): void
    {
        $mainDocumentRepository = self::fromContainer(MainDocumentRepository::class);
        $iterable = $mainDocumentRepository->getPublishedMainDocumentsIterable();

        $allMainDocuments = iterator_to_array($iterable, false);
        Assert::allIsInstanceOf($allMainDocuments, AbstractMainDocument::class);

        $wooIndexMainDocument1 = WooIndexWooDecisionStory::get('mainDocument-1');
        Assert::isInstanceOf($wooIndexMainDocument1, WooDecisionMainDocument::class);

        $wooIndexMainDocument2 = WooIndexWooDecisionStory::get('mainDocument-2');
        Assert::isInstanceOf($wooIndexMainDocument2, WooDecisionMainDocument::class);

        $annualReportMainDocument = WooIndexAnnualReportStory::get('mainDocument');
        Assert::isInstanceOf($annualReportMainDocument, AnnualReportMainDocument::class);

        $covenantMainDocument = WooIndexCovenantStory::get('mainDocument');
        Assert::isInstanceOf($covenantMainDocument, CovenantMainDocument::class);

        $expectedMainDocumentUuids = [
            $wooIndexMainDocument1->getId()->toRfc4122(),
            $wooIndexMainDocument2->getId()->toRfc4122(),
            $annualReportMainDocument->getId()->toRfc4122(),
            $covenantMainDocument->getId()->toRfc4122(),
        ];

        $this->assertCount(4, $allMainDocuments);
        foreach ($allMainDocuments as $document) {
            $this->assertContains($document->getId()->toRfc4122(), $expectedMainDocumentUuids);
        }
    }
}
