<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Document;

use Carbon\CarbonImmutable;
use Doctrine\ORM\QueryBuilder;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Shared\Tests\Story\WooIndexWooDecisionStory;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\WithStory;

use function array_map;
use function iterator_to_array;
use function strtoupper;
use function Zenstruck\Foundry\Persistence\save;

final class DocumentRepositoryTest extends SharedWebTestCase
{
    private DocumentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $repository = self::getContainer()->get(DocumentRepository::class);
        Assert::isInstanceOf($repository, DocumentRepository::class);

        $this->repository = $repository;
    }

    public function testSaveAndRemove(): void
    {
        $document = new Document();
        $document->setDocumentNr('abc123');

        $this->repository->save($document, true);
        $result = $this->repository->find($document->getId());
        self::assertEquals($document, $result);

        $this->repository->remove($document, true);
        self::assertNull(
            $this->repository->find($document->getId())
        );
    }

    public function testFindByFamilyId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierC = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::CONCEPT]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrA = 'FOO-123',
            'dossiers' => [$dossierA],
            'familyId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossierA],
            'familyId' => 456,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-789',
            'dossiers' => [$dossierB],
            'familyId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-999',
            'dossiers' => [$dossierC],
            'familyId' => 123,
        ]);

        $result = $this->repository->findByFamilyId(
            $dossierA,
            123,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());

        $result = $this->repository->findByFamilyId(
            $dossierA,
            456,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrB, $result[0]->getDocumentNr());

        $result = $this->repository->findByFamilyId(
            $dossierC,
            123,
        );
        self::assertCount(0, $result);
    }

    public function testFindByThreadId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierC = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::CONCEPT]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrA = 'FOO-123',
            'dossiers' => [$dossierA],
            'threadId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossierA],
            'threadId' => 456,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-789',
            'dossiers' => [$dossierB],
            'threadId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-999',
            'dossiers' => [$dossierC],
            'threadId' => 123,
        ]);

        $result = $this->repository->findByThreadId(
            $dossierA,
            123,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());

        $result = $this->repository->findByThreadId(
            $dossierA,
            456,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrB, $result[0]->getDocumentNr());

        $result = $this->repository->findByThreadId(
            $dossierC,
            123,
        );
        self::assertCount(0, $result);
    }

    public function testPagecount(): void
    {
        DocumentFactory::createOne([
            'documentNr' => 'FOO-123',
            'fileInfo' => FileInfoFactory::createone([
                'pageCount' => 100,
            ]),
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-456',
            'fileInfo' => FileInfoFactory::createone([
                'pageCount' => 100,
            ]),
        ]);

        self::assertEquals(200, $this->repository->pagecount());
    }

    public function testGetRelatedDocumentsByThread(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        $documentA = DocumentFactory::createOne([
            'documentNr' => 'FOO-123',
            'dossiers' => [$dossierA],
            'threadId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossierA],
            'threadId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-789',
            'dossiers' => [$dossierA],
            'threadId' => 456,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-999',
            'dossiers' => [$dossierB],
            'threadId' => 123,
        ]);

        /**
         * @var QueryBuilder $queryBuilder
         */
        $queryBuilder = $this->repository->getRelatedDocumentsByThread(
            $dossierA,
            $documentA,
        );

        /**
         * @var array<array-key, Document> $result
         */
        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertEquals($documentNrB, $result[0]->getDocumentNr());
    }

    public function testGetRelatedDocumentsByFamily(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        $documentA = DocumentFactory::createOne([
            'documentNr' => 'FOO-123',
            'dossiers' => [$dossierA],
            'familyId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossierA],
            'familyId' => 123,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-789',
            'dossiers' => [$dossierA],
            'familyId' => 456,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-999',
            'dossiers' => [$dossierB],
            'familyId' => 123,
        ]);

        /**
         * @var QueryBuilder $queryBuilder
         */
        $queryBuilder = $this->repository->getRelatedDocumentsByFamily(
            $dossierA,
            $documentA,
        );

        /**
         * @var array<array-key, Document> $result
         */
        $result = $queryBuilder->getQuery()->getResult();

        self::assertCount(1, $result);
        self::assertEquals($documentNrB, $result[0]->getDocumentNr());
    }

    public function testGetRevokedDocumentsInPublicDossiers(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::CONCEPT]);

        $docA = DocumentFactory::createOne([
            'documentNr' => $documentNrA = 'FOO-123',
            'dossiers' => [$dossierA],
        ]);
        $docA->withdraw(DocumentWithdrawReason::DATA_IN_DOCUMENT, '');
        save($docA);

        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossierA],
            'suspended' => true,
        ]);

        // This one should not be matched, not suspended and not withdrawn
        DocumentFactory::createOne([
            'documentNr' => 'FOO-789',
            'dossiers' => [$dossierA],
            'suspended' => false,
        ]);

        // This one should not be matched, not a public dossier
        DocumentFactory::createOne([
            'documentNr' => 'FOO-999',
            'dossiers' => [$dossierB],
            'suspended' => true,
        ]);

        /**
         * @var array<array-key, Document> $result
         */
        $result = $this->repository->getRevokedDocumentsInPublicDossiers();

        self::assertCount(2, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());
        self::assertEquals($documentNrB, $result[1]->getDocumentNr());
    }

    public function testGetDocumentSearchEntry(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [$dossier],
        ]);

        $result = $this->repository->getDocumentSearchEntry($documentNr);

        self::assertNotNull($result);
        self::assertEquals($documentNr, $result->documentNr);
    }

    public function testFindByDocumentNr(): void
    {
        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
        ]);

        $result = $this->repository->findByDocumentNr($documentNr);
        self::assertNotNull($result);
        self::assertEquals($documentNr, $result[0]->getDocumentNr());
    }

    public function testGetAllDocumentNumbersForDossier(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrA = 'FOO-123',
            'dossiers' => [$dossier],
        ]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossier],
        ]);

        $result = $this->repository->getAllDocumentNumbersForDossier($dossier);

        self::assertEqualsCanonicalizing([$documentNrA, $documentNrB], $result);
    }

    public function testGetDossierDocumentsQueryBuilder(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [$dossier],
        ]);

        DocumentFactory::createOne([
            'documentNr' => 'FOO-456',
        ]);

        $result = $this->repository->getAllDossierDocumentsWithDossiers($dossier);

        self::assertCount(1, $result);
        self::assertEquals($documentNr, $result[0]->getDocumentNr());
    }

    public function testFindOneByDossierAndDocumentId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        DocumentFactory::createOne([
            'documentId' => $documentId = 'FOO-123',
            'dossiers' => [$dossier],
        ]);

        $result = $this->repository->findOneByDossierAndDocumentId($dossier, $documentId);

        self::assertNotNull($result);
        self::assertEquals($documentId, $result->getDocumentId());
    }

    public function testFindOneByDossierAndId(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        $document = DocumentFactory::createOne([
            'documentId' => $documentId = 'FOO-123',
            'dossiers' => [$dossier],
        ]);

        $result = $this->repository->findOneByDossierAndId($dossier, $document->getId());

        self::assertSame($document, $result);
    }

    public function testFindOneByDossierNrAndDocumentNr(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        $document = DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [$dossier],
        ]);

        $result = $this->repository->findOneByDossierNrAndDocumentNr(
            $dossier->getDocumentPrefix(),
            $dossier->getDossierNr(),
            $documentNr,
        );

        self::assertSame($document, $result);
    }

    public function testGetDossierDocumentsForPaginationQueryBuilder(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);

        $documentNrA = 'FOO-123';
        $documentNrB = 'FOO-456';

        DocumentFactory::createOne([
            'documentNr' => $documentNrA,
            'dossiers' => [$dossier],
            'documentDate' => CarbonImmutable::now(),
        ]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrB,
            'dossiers' => [$dossier],
            'documentDate' => CarbonImmutable::now()->addDay(),
        ]);

        /**
         * @var list<Document> $result
         */
        $result = $this->repository->getDossierDocumentsForPaginationQuery($dossier)->getResult();

        self::assertCount(2, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());
        self::assertEquals($documentNrB, $result[1]->getDocumentNr());
    }

    #[WithStory(WooIndexWooDecisionStory::class)]
    public function testGetPublishedDocumentsIterable(): void
    {
        $iterable = $this->repository->getPublishedDocumentsIterable();

        /** @var list<Document> $allDocuments */
        $allDocuments = iterator_to_array($iterable, false);

        /** @var non-empty-list<Document> $documents */
        $documents = [
            ...WooIndexWooDecisionStory::getPool('documents-1'),
            ...WooIndexWooDecisionStory::getPool('documents-2'),
        ];

        $expectedDocumentUuids = array_map(
            fn (Document $document): string => $document->getId()->toRfc4122(),
            $documents,
        );

        $this->assertCount(20, $allDocuments);
        foreach ($allDocuments as $document) {
            $this->assertContains($document->getId()->toRfc4122(), $expectedDocumentUuids);
        }
    }

    public function testFindOneByDocumentNrCaseInsensitive(): void
    {
        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-xx-123',
        ]);

        $result = $this->repository->findOneByDocumentNrCaseInsensitive(strtoupper($documentNr));

        self::assertNotNull($result);
        self::assertEquals($documentNr, $result->getDocumentNr());
    }

    public function testGetDocumentCaseNrs(): void
    {
        $document = DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-xx-123',
        ]);

        InquiryFactory::createOne([
            'caseNr' => $caseNr = 'FOO-123',
            'documents' => [$document],
        ]);

        $documentCaseNrs = $this->repository->getDocumentCaseNrs(strtoupper($documentNr));

        self::assertFalse($documentCaseNrs->isDocumentNotFound());
        self::assertEquals($document->getId(), $documentCaseNrs->documentId);
        self::assertEquals($documentCaseNrs->caseNumbers->values, [$caseNr]);
    }

    public function testGetPublicInquiryDocumentsWithDossiers(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $documentA1 = DocumentFactory::createOne(['dossiers' => [$dossierA]]);
        $documentA2 = DocumentFactory::createOne(['dossiers' => [$dossierA]]);

        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $documentB1 = DocumentFactory::createOne(['dossiers' => [$dossierB]]);

        $dossierConcept = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::CONCEPT]);
        $documentConcept = DocumentFactory::createOne(['dossiers' => [$dossierConcept]]);

        $inquiry = InquiryFactory::createOne([
            'caseNr' => 'FOO-123',
            'dossiers' => [
                $dossierA,
                $dossierB,
                $dossierConcept,
            ],
            'documents' => [
                $documentA1,
                $documentA2,
                $documentB1,
                $documentConcept,
            ],
            'organisation' => $organisation,
        ]);

        self::assertEqualsCanonicalizing(
            [
                $documentA1->getId(),
                $documentA2->getId(),
                $documentB1->getId(),
                // Important: the document from the concept dossier must be excluded!
            ],
            array_map(
                static fn (Document $document): Uuid => $document->getId(),
                $this->repository->getPublicInquiryDocumentsWithDossiers($inquiry),
            ),
        );
    }

    public function testFindByDossierAndExternalId(): void
    {
        $externalId = ExternalId::create($this->getFaker()->uuid());

        $dossier = WooDecisionFactory::createOne();
        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'externalId' => $externalId,
        ]);

        $result = $this->repository->findByDossierAndExternalId($dossier, $externalId);

        self::assertEquals($externalId, $result?->getExternalId());
    }

    public function testFindByDossierAndExternalIdWhenNotLinkedReturnsNull(): void
    {
        $externalId = ExternalId::create($this->getFaker()->uuid());

        $dossier = WooDecisionFactory::createOne();
        DocumentFactory::createOne([
            'externalId' => $externalId,
        ]);

        $result = $this->repository->findByDossierAndExternalId($dossier, $externalId);

        self::assertNull($result);
    }

    public function testHasIncompleteDocumentsReturnsFalseForCompleteDocuments(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-001',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-002',
            'judgement' => Judgement::NOT_PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForMissingDocumentNr(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => '',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForWhitespaceDocumentNr(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => '   ',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForMissingJudgement(): void
    {
        $dossier = WooDecisionFactory::createOne();

        // Create document manually since setJudgement requires non-null but property is nullable
        $document = new Document();
        $document->setDocumentNr('DOC-001');
        $document->setFileInfo(FileInfoFactory::createOne(['uploaded' => true]));
        $document->addDossier($dossier);

        $this->repository->save($document, true);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForPublicDocumentWithoutFile(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-001',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForPartialPublicDocumentWithoutFile(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-001',
            'judgement' => Judgement::PARTIAL_PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseForWithdrawnDocumentWithoutFile(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::new()
            ->withdrawn()
            ->create([
                'dossiers' => [$dossier],
                'documentNr' => 'DOC-001',
                'judgement' => Judgement::PUBLIC,
            ]);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseForSuspendedDocumentWithoutFile(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-001',
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseForAlreadyPublicDocumentWithoutFile(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-001',
            'judgement' => Judgement::ALREADY_PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseForNotPublicDocumentWithoutFile(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-001',
            'judgement' => Judgement::NOT_PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForIncompleteReferredDocument(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $referredDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-REFERRED',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        $mainDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-MAIN',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $mainDocument->addRefersTo($referredDocument);
        $this->repository->save($mainDocument, true);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForMultipleLevelsOfReferrals(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $deepReferredDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => '',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $middleDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-MIDDLE',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $mainDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-MAIN',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $middleDocument->addRefersTo($deepReferredDocument);
        $this->repository->save($middleDocument, true);

        $mainDocument->addRefersTo($middleDocument);
        $this->repository->save($mainDocument, true);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseWhenReferredDocumentIsComplete(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $referredDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-REFERRED',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $mainDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-MAIN',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $mainDocument->addRefersTo($referredDocument);
        $this->repository->save($mainDocument, true);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueForMultipleIncompleteDocuments(): void
    {
        $dossier = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => '',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-002',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseForDifferentDossier(): void
    {
        $dossierA = WooDecisionFactory::createOne();
        $dossierB = WooDecisionFactory::createOne();

        DocumentFactory::createOne([
            'dossiers' => [$dossierB],
            'documentNr' => '',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossierA->getId()));
    }

    public function testHasIncompleteDocumentsReturnsTrueWhenReferredDocumentHasMissingJudgement(): void
    {
        $dossier = WooDecisionFactory::createOne();

        // Create referred document manually with null judgement
        $referredDocument = new Document();
        $referredDocument->setDocumentNr('DOC-REFERRED');
        $referredDocument->setFileInfo(FileInfoFactory::createOne(['uploaded' => true]));
        $referredDocument->addDossier($dossier);
        $this->repository->save($referredDocument, true);

        $mainDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-MAIN',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $mainDocument->addRefersTo($referredDocument);
        $this->repository->save($mainDocument, true);

        self::assertTrue($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsReturnsFalseForComplexScenarioWithAllComplete(): void
    {
        $dossier = WooDecisionFactory::createOne();

        // Create multiple referred documents, all complete
        $referred1 = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-REF-1',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $referred2 = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-REF-2',
            'judgement' => Judgement::NOT_PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        $referred3 = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-REF-3',
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);

        $mainDocument = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-MAIN',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $mainDocument->addRefersTo($referred1);
        $mainDocument->addRefersTo($referred2);
        $mainDocument->addRefersTo($referred3);
        $this->repository->save($mainDocument, true);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }

    public function testHasIncompleteDocumentsHandlesCircularReferrals(): void
    {
        $dossier = WooDecisionFactory::createOne();

        $doc1 = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-1',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        $doc2 = DocumentFactory::createOne([
            'dossiers' => [$dossier],
            'documentNr' => 'DOC-2',
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => true]),
        ]);

        // Create circular reference
        $doc1->addRefersTo($doc2);
        $doc2->addRefersTo($doc1);
        $this->repository->save($doc1, true);
        $this->repository->save($doc2, true);

        self::assertFalse($this->repository->hasIncompleteDocumentsForDossier($dossier->getId()));
    }
}
