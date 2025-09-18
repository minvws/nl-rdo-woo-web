<?php

declare(strict_types=1);

namespace App\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Service\Security\ApplicationMode\ApplicationMode;
use App\Tests\Factory\DocumentFactory;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class WooDecisionRepositoryTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    private function getRepository(): WooDecisionRepository
    {
        /** @var WooDecisionRepository */
        return self::getContainer()->get(WooDecisionRepository::class);
    }

    public function testGetDossierCounts(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
        ]);

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $result = $this->getRepository()->getDossierCounts($wooDecision->_real());

        $this->assertSame(3, $result->getTotalDocumentCount());
        $this->assertTrue($result->hasDocuments());
        $this->assertSame(2, $result->getPublicDocumentCount());
    }

    public function testGetDossierReferencesForDocument(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        $doc = DocumentFactory::createone([
            'dossiers' => [$wooDecision],
        ]);

        $result = $this->getRepository()->getDossierReferencesForDocument($doc->getDocumentNr());
        $dossierReference = reset($result);

        self::assertInstanceOf(DossierReference::class, $dossierReference);
        self::assertEquals($wooDecision->getType(), $dossierReference->getType());
        self::assertEquals($wooDecision->getDossierNr(), $dossierReference->getDossierNr());
        self::assertEquals($wooDecision->getTitle(), $dossierReference->getTitle());
        self::assertEquals($wooDecision->getDocumentPrefix(), $dossierReference->getDocumentPrefix());
    }

    public function testSearchResultViewModel(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
                'pageCount' => 5,
            ]),
        ]);

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
                'pageCount' => 2,
            ]),
        ]);

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
                'pageCount' => 7,
            ]),
        ]);

        $result = $this->getRepository()->getSearchResultViewModel(
            $wooDecision->_real()->getDocumentPrefix(),
            $wooDecision->_real()->getDossierNr(),
            ApplicationMode::PUBLIC,
        );

        $this->assertNotNull($result);
        $this->assertEquals($wooDecision->_real()->getTitle(), $result->title);
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

        $result = $this->getRepository()->findAllForOrganisation(
            $organisationA->_real()
        );

        $this->assertCount(2, $result);

        $dosserNrResults = array_map(
            static fn (WooDecision $decision): string => $decision->getDossierNr(),
            $result,
        );

        $this->assertContains($wooDecisionA->getDossierNr(), $dosserNrResults);
        $this->assertContains($wooDecisionC->getDossierNr(), $dosserNrResults);
    }

    public function testFindOne(): void
    {
        $wooDecision = WooDecisionFactory::createOne()->_real();

        $result = $this->getRepository()->findOne($wooDecision->getId());

        $this->assertEquals($wooDecision->getId(), $result->getId());
    }

    public function testFindOneThrowsNoResultException(): void
    {
        self::expectExceptionObject(new NoResultException());

        $this->getRepository()->findOne(Uuid::v6());
    }

    public function testGetDocumentsForBatchDownload(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        // Not uploaded, so should not be included
        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => false,
            ]),
        ]);

        $document = DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        // Suspended, so should not be included
        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'suspended' => true,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
        ]);

        // Not public, so should not be included
        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::NOT_PUBLIC,
        ]);

        $result = $this->getRepository()
            ->getDocumentsForBatchDownload($wooDecision->_real())
            ->getQuery()
            ->getResult();

        $this->assertEquals(
            $document->_real()->getId(),
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

        $result = $this->getRepository()->getPubliclyAvailable();

        $dosserNrResults = array_map(
            static fn (WooDecision $decision): string => $decision->getDossierNr(),
            $result,
        );

        $this->assertNotContains($wooDecisionA->getDossierNr(), $dosserNrResults);
        $this->assertContains($wooDecisionB->getDossierNr(), $dosserNrResults);
    }

    public function testGetNotificationCounts(): void
    {
        $wooDecision = WooDecisionFactory::createOne();

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => false,
            ]),
            'suspended' => false,
        ]);

        $docB = DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
            'suspended' => true,
        ]);
        $docB->withdraw(DocumentWithdrawReason::DATA_IN_DOCUMENT, '');
        $docB->_save();

        DocumentFactory::createone([
            'dossiers' => [$wooDecision],
            'judgement' => Judgement::PUBLIC,
            'fileInfo' => FileInfoFactory::createone([
                'uploaded' => true,
            ]),
            'suspended' => true,
        ]);

        $result = $this->getRepository()->getNotificationCounts($wooDecision);

        $this->assertEquals(1, $result['missing_uploads']);
        $this->assertEquals(1, $result['withdrawn']);
        $this->assertEquals(2, $result['suspended']);
    }
}
