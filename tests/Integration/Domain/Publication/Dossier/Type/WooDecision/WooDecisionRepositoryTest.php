<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type\WooDecision;

use Doctrine\ORM\NoResultException;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Integration\SharedWebTestCase;
use Symfony\Component\Uid\Uuid;

use function array_map;
use function reset;
use function Zenstruck\Foundry\Persistence\save;

final class WooDecisionRepositoryTest extends SharedWebTestCase
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

        $result = $this->getRepository()->getDossierCounts($wooDecision);

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
            $wooDecision->getDocumentPrefix(),
            $wooDecision->getDossierNr(),
            ApplicationMode::PUBLIC,
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

        $result = $this->getRepository()->findAllForOrganisation(
            $organisationA
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
        $wooDecision = WooDecisionFactory::createOne();

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
        save($docB);

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
