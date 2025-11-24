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
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Persistence\Proxy;

final class MainDocumentRepositoryTest extends SharedWebTestCase
{
    private MainDocumentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(MainDocumentRepository::class);
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    #[WithStory(WooIndexAnnualReportStory::class)]
    #[WithStory(WooIndexCovenantStory::class)]
    public function testGetPublishedMainDocumentsIterable(): void
    {
        $iterable = $this->repository->getPublishedMainDocumentsIterable();

        /** @var list<AbstractMainDocument> $allMainDocuments */
        $allMainDocuments = iterator_to_array($iterable, false);

        /** @var Proxy<WooDecisionMainDocument> $wooIndexMainDocument1 */
        $wooIndexMainDocument1 = WooIndexWooDecisionStory::get('mainDocument-1');

        /** @var Proxy<WooDecisionMainDocument> $wooIndexMainDocument2 */
        $wooIndexMainDocument2 = WooIndexWooDecisionStory::get('mainDocument-2');

        /** @var Proxy<AnnualReportMainDocument> $annualReportMainDocument */
        $annualReportMainDocument = WooIndexAnnualReportStory::get('mainDocument');

        /** @var Proxy<CovenantMainDocument> $covenantMainDocument */
        $covenantMainDocument = WooIndexCovenantStory::get('mainDocument');

        $expectedMainDocumentUuids = [
            $wooIndexMainDocument1->_real()->getId()->toRfc4122(),
            $wooIndexMainDocument2->_real()->getId()->toRfc4122(),
            $annualReportMainDocument->_real()->getId()->toRfc4122(),
            $covenantMainDocument->_real()->getId()->toRfc4122(),
        ];

        $this->assertCount(4, $allMainDocuments);
        foreach ($allMainDocuments as $document) {
            $this->assertContains($document->getId()->toRfc4122(), $expectedMainDocumentUuids);
        }
    }
}
