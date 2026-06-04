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

final class InquiryRepositoryTest extends SharedWebTestCase
{
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
            'dossiers' => [$wooDecisionA],
            'documents' => [$docA, $docB, $docC, $docD, $docE],
        ]);

        $result = self::fromContainer(InquiryRepository::class)
            ->getDocumentsForBatchDownload($inquiry, $wooDecisionA)
            ->getQuery()
            ->getResult();

        $this->assertEquals([$docB], $result);
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
        ]);

        $result = self::fromContainer(InquiryRepository::class)->countDocumentsByJudgement($inquiry);

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

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$conceptWooDecision, $publishedWooDecision, $previewWooDecision],
        ]);

        // Only $previewWooDecision and $publishedWooDecision should be counted
        $this->assertEquals(2, self::fromContainer(InquiryRepository::class)->countPubliclyAvailableDossiers($inquiry));
    }

    public function testGetDossiersForInquiryQueryBuilder(): void
    {
        $conceptWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::CONCEPT]);
        $previewWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW]);
        $publishedWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED]);

        $docInPreviewAndInquiryA = DocumentFactory::createone(['dossiers' => [$previewWooDecision]]);
        $docInPreviewAndInquiryB = DocumentFactory::createone(['dossiers' => [$previewWooDecision]]);
        $docInPublishedAndInquiry = DocumentFactory::createone(['dossiers' => [$publishedWooDecision]]);

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$conceptWooDecision, $publishedWooDecision, $previewWooDecision],
            'documents' => [$docInPreviewAndInquiryA, $docInPreviewAndInquiryB, $docInPublishedAndInquiry],
        ]);

        $result = self::fromContainer(InquiryRepository::class)
            ->getDossiersForInquiryQueryBuilder($inquiry)
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $result);
        $this->assertContainsEquals(
            [
                0 => $publishedWooDecision,
                'docCount' => 1,
            ],
            $result,
        );
        $this->assertContainsEquals(
            [
                0 => $previewWooDecision,
                'docCount' => 2,
            ],
            $result,
        );
    }

    public function testGetQueryWithDocCountAndDossierCount(): void
    {
        $conceptWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::CONCEPT]);
        $previewWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW]);
        $publishedWooDecision = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        $otherWooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createone(['dossiers' => [$conceptWooDecision]]);
        $docInPreviewAndInquiryA = DocumentFactory::createone(['dossiers' => [$previewWooDecision]]);
        $docInPreviewAndInquiryB = DocumentFactory::createone(['dossiers' => [$previewWooDecision]]);
        DocumentFactory::createone(['dossiers' => [$previewWooDecision]]);
        $docInPublishedAndInquiry = DocumentFactory::createone(['dossiers' => [$publishedWooDecision]]);
        DocumentFactory::createone(['dossiers' => [$otherWooDecision]]);

        $inquiry = InquiryFactory::createOne([
            'dossiers' => [$conceptWooDecision, $publishedWooDecision, $previewWooDecision],
            'documents' => [$docInPreviewAndInquiryA, $docInPreviewAndInquiryB, $docInPublishedAndInquiry],
        ]);

        /** @var array<array-key, array{inquiry: Inquiry, documentCount: int, dossierCount: int}> $result */
        $result = self::fromContainer(InquiryRepository::class)
            ->getQueryWithDocCountAndDossierCount($inquiry->getOrganisation())
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertEquals($result[0]['inquiry'], $inquiry);
        $this->assertEquals($result[0]['documentCount'], 3);
        $this->assertEquals($result[0]['dossierCount'], 3);
    }
}
