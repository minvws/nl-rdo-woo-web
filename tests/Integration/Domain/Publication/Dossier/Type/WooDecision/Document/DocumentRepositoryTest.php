<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\InquiryFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use App\Tests\Story\WooIndexWooDecisionStory;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Persistence\Proxy;

final class DocumentRepositoryTest extends KernelTestCase
{
    use IntegrationTestTrait;

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
            $dossierA->_real(),
            123,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());

        $result = $this->repository->findByFamilyId(
            $dossierA->_real(),
            456,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrB, $result[0]->getDocumentNr());

        $result = $this->repository->findByFamilyId(
            $dossierC->_real(),
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
            $dossierA->_real(),
            123,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());

        $result = $this->repository->findByThreadId(
            $dossierA->_real(),
            456,
        );
        self::assertCount(1, $result);
        self::assertEquals($documentNrB, $result[0]->getDocumentNr());

        $result = $this->repository->findByThreadId(
            $dossierC->_real(),
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
            $dossierA->_real(),
            $documentA->_real(),
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
            $dossierA->_real(),
            $documentA->_real(),
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
        $docA->_save();

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

        $result = $this->repository->findOneByDossierAndId($dossier->_real(), $document->getId());

        self::assertSame($document->_real(), $result);
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

        self::assertSame($document->_real(), $result);
    }

    public function testGetDossierDocumentsForPaginationQueryBuilder(): void
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

        $queryBuilder = $this->repository->getDossierDocumentsForPaginationQueryBuilder($dossier);
        $queryBuilder->addOrderBy('doc.documentNr', 'ASC');
        $result = $queryBuilder->getQuery()->getResult();

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

        /** @var non-empty-list<Proxy<Document>> $documents */
        $documents = [
            ...WooIndexWooDecisionStory::getPool('documents-1'),
            ...WooIndexWooDecisionStory::getPool('documents-2'),
        ];

        $expectedDocumentUuids = array_map(
            fn (Proxy $document): string => $document->_real()->getId()->toRfc4122(),
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
            'documents' => [$document->_real()],
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
}
