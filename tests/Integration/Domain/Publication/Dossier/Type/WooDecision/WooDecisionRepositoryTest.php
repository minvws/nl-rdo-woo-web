<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision;

use Doctrine\ORM\NoResultException;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\Uid\Uuid;

use function array_map;
use function range;
use function reset;
use function Zenstruck\Foundry\Persistence\save;

final class WooDecisionRepositoryTest extends SharedWebTestCase
{
    private WooDecisionRepository $wooDecisionRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->wooDecisionRepository = self::fromContainer(WooDecisionRepository::class);
    }

    public function testGetDossierCounts(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $result = $this->wooDecisionRepository->getDossierCounts($wooDecision);

        $this->assertSame(3, $result->getTotalDocumentCount());
        $this->assertTrue($result->hasDocuments());
        $this->assertSame(2, $result->getPublicDocumentCount());
    }

    public function testGetDossierReferencesForDocument(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $doc = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
        ]);

        $result = $this->wooDecisionRepository->getDossierReferencesForDocument($doc->getDocumentNumber());
        $dossierReference = reset($result);

        self::assertInstanceOf(DocumentNumber::class, $doc->getDocumentNumber());
        self::assertInstanceOf(DossierReference::class, $dossierReference);
        self::assertEquals($wooDecision->getType(), $dossierReference->getType());
        self::assertEquals($wooDecision->getDossierNumber(), $dossierReference->getDossierNumber());
        self::assertEquals($wooDecision->getTitle(), $dossierReference->getTitle());
        self::assertEquals($wooDecision->getDocumentPrefix(), $dossierReference->getDocumentPrefix());
    }

    public function testHasPublishedDossierForDocumentExcept(): void
    {
        $exportDossier = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        $publishedDossier = WooDecisionFactory::createOne(['status' => DossierStatus::PUBLISHED]);
        $previewDossier = WooDecisionFactory::createOne(['status' => DossierStatus::PREVIEW]);

        $documentWithPublishedDossier = DocumentFactory::createOne([
            'dossiers' => [$exportDossier, $publishedDossier],
        ]);
        $documentWithPreviewDossier = DocumentFactory::createOne([
            'dossiers' => [$exportDossier, $previewDossier],
        ]);
        $documentWithoutOtherDossier = DocumentFactory::createOne([
            'dossiers' => [$exportDossier],
        ]);

        self::assertTrue($this->wooDecisionRepository->hasPublishedDossierForDocumentExcept(
            $documentWithPublishedDossier->getDocumentNumber(),
            $exportDossier,
        ));
        self::assertFalse($this->wooDecisionRepository->hasPublishedDossierForDocumentExcept(
            $documentWithPreviewDossier->getDocumentNumber(),
            $exportDossier,
        ));
        self::assertFalse($this->wooDecisionRepository->hasPublishedDossierForDocumentExcept(
            $documentWithoutOtherDossier->getDocumentNumber(),
            $exportDossier,
        ));
    }

    public function testHasPubliclyAvailableDossierForDocument(): void
    {
        $publishedDossier = WooDecisionFactory::createOne([
            'status' => DossierStatus::PUBLISHED,
        ]);
        $previewDossier = WooDecisionFactory::createOne([
            'status' => DossierStatus::PREVIEW,
        ]);
        $conceptDossier = WooDecisionFactory::createOne([
            'status' => DossierStatus::CONCEPT,
        ]);

        $publishedDocument = DocumentFactory::createOne([
            'dossiers' => [$publishedDossier],
        ]);
        $previewDocument = DocumentFactory::createOne([
            'dossiers' => [$previewDossier],
        ]);
        $conceptDocument = DocumentFactory::createOne([
            'dossiers' => [$conceptDossier],
        ]);

        self::assertTrue($this->wooDecisionRepository->hasPubliclyAvailableDossierForDocument(
            $publishedDocument->getDocumentNumber(),
        ));
        self::assertTrue($this->wooDecisionRepository->hasPubliclyAvailableDossierForDocument(
            $previewDocument->getDocumentNumber(),
        ));
        self::assertFalse($this->wooDecisionRepository->hasPubliclyAvailableDossierForDocument(
            $conceptDocument->getDocumentNumber(),
        ));
    }

    public function testSearchResultViewModel(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
                'pageCount' => 5,
            ]),
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
                'pageCount' => 2,
            ]),
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
                'pageCount' => 7,
            ]),
        ]);

        $result = $this->wooDecisionRepository->getSearchResultViewModel(
            $wooDecision->getDocumentPrefix(),
            $wooDecision->getDossierNumber(),
            ApplicationId::PUBLIC,
        );

        $this->assertNotNull($result);
        $this->assertEquals($wooDecision->getTitle(), $result->title);
        $this->assertEquals(3, $result->documentCount);
    }

    public function testFindAllForOrganisation(): void
    {
        $organisationA = OrganisationFactory::createOne();
        $organisationB = OrganisationFactory::createOne();

        $wooDecisionA = WooDecisionFactory::createOne([
            'organisation' => $organisationA,
        ]);

        // WooDecision B: other organisation, should not be in the result
        WooDecisionFactory::createOne([
            'organisation' => $organisationB,
        ]);

        $wooDecisionC = WooDecisionFactory::createOne([
            'organisation' => $organisationA,
        ]);

        $result = $this->wooDecisionRepository->findAllForOrganisation(
            $organisationA,
        );

        $this->assertCount(2, $result);

        $dosserNrResults = array_map(
            static fn (WooDecision $decision): string => $decision->getDossierNumber(),
            $result,
        );

        $this->assertContains($wooDecisionA->getDossierNumber(), $dosserNrResults);
        $this->assertContains($wooDecisionC->getDossierNumber(), $dosserNrResults);
    }

    public function testFindOne(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $result = $this->wooDecisionRepository->findOne($wooDecision->getId());

        $this->assertEquals($wooDecision->getId(), $result->getId());
    }

    public function testFindOneThrowsNoResultException(): void
    {
        self::expectExceptionObject(new NoResultException());

        $this->wooDecisionRepository->findOne(Uuid::v6());
    }

    public function testGetDocumentsForBatchDownload(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        // Not uploaded, so should not be included
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => false,
            ]),
        ]);

        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
            ]),
        ]);

        // Suspended, so should not be included
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
            ]),
        ]);

        // Not public, so should not be included
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $result = $this->wooDecisionRepository
            ->getDocumentsForBatchDownload($wooDecision)
            ->getQuery()
            ->getResult();

        $this->assertEquals(
            $document->getId(),
            $result[0]->getId(),
        );
    }

    public function testGetPubliclyAvailableDoesNotReturnConceptDossier(): void
    {
        $wooDecisionA = WooDecisionFactory::createOne([
            'status' => DossierStatus::CONCEPT,
        ]);

        $wooDecisionB = WooDecisionFactory::createOne([
            'status' => DossierStatus::PUBLISHED,
        ]);

        $result = $this->wooDecisionRepository->getPubliclyAvailable();

        $dosserNrResults = array_map(
            static fn (WooDecision $decision): string => $decision->getDossierNumber(),
            $result,
        );

        $this->assertNotContains($wooDecisionA->getDossierNumber(), $dosserNrResults);
        $this->assertContains($wooDecisionB->getDossierNumber(), $dosserNrResults);
    }

    public function testGetNotificationCounts(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => false,
            ]),
            'suspended' => false,
        ]);

        $docB = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
            ]),
            'suspended' => true,
        ]);
        $docB->withdraw(DocumentWithdrawReason::DATA_IN_DOCUMENT, '');
        save($docB);

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => true,
            ]),
            'suspended' => true,
        ]);

        $result = $this->wooDecisionRepository->getNotificationCounts($wooDecision);

        $this->assertEquals(1, $result['missing_uploads']);
        $this->assertEquals(1, $result['withdrawn']);
        $this->assertEquals(2, $result['suspended']);
    }

    public function testFindAllLinkedToDocuments(): void
    {
        $decisionA = WooDecisionFactory::createOne();
        $decisionB = WooDecisionFactory::createOne();
        $unlinkedDecision = WooDecisionFactory::createOne();

        $sharedDocument = DocumentFactory::createOne(['dossiers' => [$decisionA, $decisionB]]);
        $ownDocument = DocumentFactory::createOne(['dossiers' => [$decisionB]]);
        DocumentFactory::createOne(['dossiers' => [$unlinkedDecision]]);

        $result = $this->wooDecisionRepository->findAllLinkedToDocuments([
            $sharedDocument->getId(),
            $ownDocument->getId(),
        ]);

        self::assertEqualsCanonicalizing(
            [$decisionA->getId()->toRfc4122(), $decisionB->getId()->toRfc4122()],
            array_map(static fn (WooDecision $wooDecision): string => $wooDecision->getId()->toRfc4122(), $result),
        );
    }

    public function testFindAllLinkedToDocumentsDeduplicatesAcrossChunks(): void
    {
        $decisionA = WooDecisionFactory::createOne();
        $decisionB = WooDecisionFactory::createOne();

        $sharedDocument = DocumentFactory::createOne(['dossiers' => [$decisionA, $decisionB]]);
        $ownDocument = DocumentFactory::createOne(['dossiers' => [$decisionB]]);

        $result = $this->wooDecisionRepository->findAllLinkedToDocuments([
            $sharedDocument->getId(),
            ...array_map(Uuid::v6(...), range(1, 999)),
            $ownDocument->getId(),
        ]);

        self::assertEqualsCanonicalizing(
            [$decisionA->getId()->toRfc4122(), $decisionB->getId()->toRfc4122()],
            array_map(static fn (WooDecision $wooDecision): string => $wooDecision->getId()->toRfc4122(), $result),
        );
    }

    public function testFindAllLinkedToDocumentsWithoutAnyDocuments(): void
    {
        WooDecisionFactory::createOne();

        self::assertSame([], $this->wooDecisionRepository->findAllLinkedToDocuments([]));
    }
}
