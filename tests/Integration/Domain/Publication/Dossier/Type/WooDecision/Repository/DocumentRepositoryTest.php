<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision\Repository;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WithdrawReason;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Webmozart\Assert\Assert;

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

    public function testFindBySearchTerm(): void
    {
        $organisation = OrganisationFactory::createOne();

        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [WooDecisionFactory::createOne(['organisation' => $organisation])],
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-1234',
            'dossiers' => [WooDecisionFactory::createOne(['organisation' => $organisation])],
        ]);

        $result = $this->repository->findBySearchTerm(
            $documentNr,
            10,
            $organisation->_real(),
        );

        self::assertCount(2, $result);
    }

    public function testFindBySearchTermFilteredByUuid(): void
    {
        $organisation = OrganisationFactory::createOne();
        $dossierOne = WooDecisionFactory::createOne(['organisation' => $organisation]);

        DocumentFactory::createOne([
            'documentNr' => $documentNr = 'FOO-123',
            'dossiers' => [$dossierOne],
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-1235',
            'dossiers' => [WooDecisionFactory::createOne(['organisation' => $organisation])],
        ]);

        $result = $this->repository->findBySearchTerm(
            $documentNr,
            10,
            $organisation->_real(),
            dossierId: $dossierOne->_real()->getId(),
        );

        self::assertCount(1, $result);
    }

    public function testPagecount(): void
    {
        DocumentFactory::createOne([
            'documentNr' => 'FOO-123',
            'pageCount' => 100,
        ]);
        DocumentFactory::createOne([
            'documentNr' => 'FOO-456',
            'pageCount' => 100,
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
        $docA->withdraw(WithdrawReason::DATA_IN_DOCUMENT, '');
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

    public function testGetPublishedDocuments(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossierA = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::PUBLISHED]);
        $dossierB = WooDecisionFactory::createOne(['organisation' => $organisation, 'status' => DossierStatus::CONCEPT]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrA = 'FOO-123',
            'dossiers' => [$dossierA],
        ]);

        DocumentFactory::createOne([
            'documentNr' => $documentNrB = 'FOO-456',
            'dossiers' => [$dossierA],
        ]);

        // This one should not be matched, not a public dossier
        DocumentFactory::createOne([
            'documentNr' => 'FOO-999',
            'dossiers' => [$dossierB],
        ]);

        /**
         * @var array<array-key, Document> $result
         */
        $result = $this->repository->getPublishedDocuments();

        self::assertCount(2, $result);
        self::assertEquals($documentNrA, $result[0]->getDocumentNr());
        self::assertEquals($documentNrB, $result[1]->getDocumentNr());
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

    public function testGetAllDossierDocumentsWithDossiers(): void
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
}