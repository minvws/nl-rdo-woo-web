<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Inquiry;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Webmozart\Assert\Assert;

final class InquiryRepositoryTest extends SharedWebTestCase
{
    private InquiryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(InquiryRepository::class);
        Assert::isInstanceOf($repository, InquiryRepository::class);

        $this->repository = $repository;
    }

    public function testGetDocumentsForBatchDownload(): void
    {
        $wooDecisionA = WooDecisionFactory::createOne();
        $wooDecisionB = WooDecisionFactory::createOne();

        // Not uploaded, so should not be included
        $docA = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => false,
            ]),
        ]);

        $docB = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        // Suspended, so should not be included
        $docC = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        // Not public, so should not be included
        $docD = DocumentFactory::createone([
            'dossiers' => [$wooDecisionA],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        // Other dossier, so should not be included
        $docE = DocumentFactory::createone([
            'dossiers' => [$wooDecisionB],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$wooDecisionA->_real()],
            'documents' => [$docA, $docB, $docC, $docD, $docE],
        ]);

        $result = $this->repository
            ->getDocumentsForBatchDownload($inquiry->_real(), $wooDecisionA->_real())
            ->getQuery()
            ->getResult();

        $this->assertEquals([$docB->_real()], $result);
    }

    public function testCountDocumentsByJudgement(): void
    {
        $organisation = OrganisationFactory::new()->create();
        $wooDecision = WooDecisionFactory::new()->create([
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement([DossierStatus::PREVIEW, DossierStatus::PUBLISHED]),
        ]);

        $documentsForWooDecision = DocumentFactory::createMany(2, ['dossiers' => [$wooDecision], 'judgement' => Judgement::NOT_PUBLIC]);

        $documentsForWooDecision[] = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
        ]);

        $documentsForWooDecision[] = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
        ]);

        $withDrawnDocument = DocumentFactory::createOne(['dossiers' => [$wooDecision], 'judgement' => Judgement::PUBLIC]);
        $withDrawnDocument->withdraw(DocumentWithdrawReason::DATA_IN_DOCUMENT, '');
        $documentsForWooDecision[] = $withDrawnDocument;

        $inquiry = InquiryFactory::createOne([
            'organisation' => $organisation,
            'dossiers' => [$wooDecision],
            'documents' => [
                ...$documentsForWooDecision,
            ],
        ])->_real();

        $result = $this->repository->countDocumentsByJudgement($inquiry);

        self::assertEquals(5, $result['total']);
        self::assertEquals(3, $result['public']);
        self::assertEquals(1, $result['public_withdrawn']);
        self::assertEquals(2, $result['public_suspended']);
        self::assertEquals(0, $result['partial_public']);
        self::assertEquals(0, $result['partial_public_withdrawn']);
        self::assertEquals(0, $result['partial_public_suspended']);
        self::assertEquals(0, $result['already_public']);
        self::assertEquals(2, $result['not_public']);
    }

    public function testCountPubliclyAvailableDossiers(): void
    {
        $conceptWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::CONCEPT]);
        $previewWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW]);
        $publishedWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        $otherWooDecision = WooDecisionFactory::createOne();

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$conceptWooDecision, $publishedWooDecision, $previewWooDecision],
        ]);

        // Only $previewWooDecision and $publishedWooDecision should be counted
        $this->assertEquals(2, $this->repository->countPubliclyAvailableDossiers($inquiry));
    }

    public function testGetDossiersForInquiryQueryBuilder(): void
    {
        $conceptWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::CONCEPT])->_real();
        $previewWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW])->_real();
        $publishedWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED])->_real();
        $otherWooDecision = WooDecisionFactory::createOne()->_real();

        $docInConcept = DocumentFactory::createone(['dossiers' => [$conceptWooDecision]])->_real();
        $docInPreviewAndInquiryA = DocumentFactory::createone(['dossiers' => [$previewWooDecision]])->_real();
        $docInPreviewAndInquiryB = DocumentFactory::createone(['dossiers' => [$previewWooDecision]])->_real();
        $docInPreviewWithoutInquiry = DocumentFactory::createone(['dossiers' => [$previewWooDecision]])->_real();
        $docInPublishedAndInquiry = DocumentFactory::createone(['dossiers' => [$publishedWooDecision]])->_real();
        $docInOther = DocumentFactory::createone(['dossiers' => [$otherWooDecision]])->_real();

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$conceptWooDecision, $publishedWooDecision, $previewWooDecision],
            'documents' => [$docInPreviewAndInquiryA, $docInPreviewAndInquiryB, $docInPublishedAndInquiry],
        ]);

        $result = $this->repository->getDossiersForInquiryQueryBuilder($inquiry->_real())
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $result);
        $this->assertContainsEquals(
            [
                0 => $publishedWooDecision,
                'docCount' => 1,
            ],
            $result
        );
        $this->assertContainsEquals(
            [
                0 => $previewWooDecision,
                'docCount' => 2,
            ],
            $result
        );
    }

    public function testGetQueryWithDocCountAndDossierCount(): void
    {
        $conceptWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::CONCEPT])->_real();
        $previewWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW])->_real();
        $publishedWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED])->_real();
        $otherWooDecision = WooDecisionFactory::createOne()->_real();

        DocumentFactory::createone(['dossiers' => [$conceptWooDecision]])->_real();
        $docInPreviewAndInquiryA = DocumentFactory::createone(['dossiers' => [$previewWooDecision]])->_real();
        $docInPreviewAndInquiryB = DocumentFactory::createone(['dossiers' => [$previewWooDecision]])->_real();
        DocumentFactory::createone(['dossiers' => [$previewWooDecision]])->_real();
        $docInPublishedAndInquiry = DocumentFactory::createone(['dossiers' => [$publishedWooDecision]])->_real();
        DocumentFactory::createone(['dossiers' => [$otherWooDecision]])->_real();

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$conceptWooDecision, $publishedWooDecision, $previewWooDecision],
            'documents' => [$docInPreviewAndInquiryA, $docInPreviewAndInquiryB, $docInPublishedAndInquiry],
        ])->_real();

        /** @var array<array{inquiry: Inquiry, documentCount: int, dossierCount: int}> $result */
        $result = $this->repository->getQueryWithDocCountAndDossierCount($inquiry->getOrganisation())
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($result[0]['inquiry'], $inquiry);
        $this->assertEquals($result[0]['documentCount'], 3);
        $this->assertEquals($result[0]['dossierCount'], 3);
    }
}
