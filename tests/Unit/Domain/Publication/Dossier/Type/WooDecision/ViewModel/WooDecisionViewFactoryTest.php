<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department as DepartmentEntity;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\Department;
use Shared\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\Subject as SubjectViewModel;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocument;
use Shared\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use Shared\Tests\Story\DepartmentEnum;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\RouterInterface;

final class WooDecisionViewFactoryTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $dossierRepository;
    private DepartmentViewFactory&MockInterface $departmentViewFactory;
    private MainDocumentViewFactory&MockInterface $mainDocumentViewFactory;
    private CommonDossierPropertiesViewFactory&MockInterface $commonDossierPropertiesViewFactory;
    private RouterInterface&MockInterface $router;
    private WooDecisionViewFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossierRepository = Mockery::mock(WooDecisionRepository::class);
        $this->dossierRepository->expects('getDossierCounts')->andReturn(Mockery::mock(DossierCounts::class));

        $this->departmentViewFactory = Mockery::mock(DepartmentViewFactory::class);
        $this->mainDocumentViewFactory = Mockery::mock(MainDocumentViewFactory::class);
        $this->commonDossierPropertiesViewFactory = Mockery::mock(CommonDossierPropertiesViewFactory::class);
        $this->router = Mockery::mock(RouterInterface::class);

        $this->factory = new WooDecisionViewFactory(
            $this->dossierRepository,
            $this->departmentViewFactory,
            $this->commonDossierPropertiesViewFactory,
            $this->mainDocumentViewFactory,
            $this->router,
        );
    }

    public function testMake(): void
    {
        $department = Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $mainDocument = Mockery::mock(WooDecisionMainDocument::class);

        $expectedDepartment = new Department(
            DepartmentEnum::VWS->value,
            feedbackContent: null,
            responsibilityContent: null,
        );

        $this->departmentViewFactory
            ->expects('makeCollection')
            ->with($departments)
            ->andReturn(new ArrayCollection([$expectedDepartment]));

        $isInventoryRequired = $expectedisInventoryRequired = true;
        $decisionDate = $expectedDecisionDate = $this->getRandomDate();
        $decisionDocument = $mainDocument;
        $dateFrom = null;
        $dateTo = $expectedDateTo = $this->getRandomDate();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDossierNr')->andReturn('my dossier nr');
        $dossier->expects('getDocumentPrefix')->andReturn('my document prefix');
        $dossier->expects('getDepartments')->andReturn($departments);
        $dossier->expects('isInventoryRequired')->andReturn($isInventoryRequired);
        $dossier->expects('isInventoryOptional')->andReturn(false);
        $dossier->expects('canProvideInventory')->andReturn(true);
        $dossier->expects('getDecision')->andReturn(DecisionType::NOT_PUBLIC);
        $dossier->expects('getDecisionDate')->andReturn($decisionDate);
        $dossier->expects('getMainDocument')->andReturn($decisionDocument);
        $dossier->expects('getDateFrom')->andReturn($dateFrom);
        $dossier->expects('getDateTo')->andReturn($dateTo);
        $dossier->expects('getPublicationReason')->andReturn(PublicationReason::WOO_REQUEST);

        $this->mainDocumentViewFactory
            ->expects('make')
            ->with($dossier, $mainDocument)
            ->andReturn($expectedMainDocumentView = Mockery::mock(MainDocument::class));

        $this->commonDossierPropertiesViewFactory
            ->expects('make')
            ->andReturn(new CommonDossierProperties(
                dossierId: $expectedUuid = 'my uuid',
                dossierNr: $expectedDossierNr = 'my dossier nr',
                documentPrefix: $expectedDocumentPrefix = 'my document prefix',
                isPreview: $expectedIsPreview = true,
                title: $expectedTitle = 'my title',
                pageTitle: $expectedPageTitle = 'my page title',
                publicationDate: $publicationDate = new DateTimeImmutable(),
                mainDepartment: $expectedMainDepartment = new Department(
                    name: DepartmentEnum::VWS->value,
                    feedbackContent: null,
                    responsibilityContent: null,
                ),
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::COVENANT,
                subject: $expectedSubject = Mockery::mock(SubjectViewModel::class),
            ));

        $this->router
            ->expects('generate')
            ->with('app_search', ['dnr' => ['my document prefix|my dossier nr']])
            ->andReturn($expectedDocumentSearchUrl = '/foo/var');

        $result = $this->factory->make($dossier);

        $this->assertInstanceOf(DossierCounts::class, $result->counts);
        $this->assertSame($expectedisInventoryRequired, $result->isInventoryRequired);
        $this->assertSame(DecisionType::NOT_PUBLIC, $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($expectedMainDocumentView, $result->mainDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame(PublicationReason::WOO_REQUEST, $result->publicationReason);
        $this->assertSame($expectedUuid, $result->getDossierId());
        $this->assertSame($expectedDossierNr, $result->getDossierNr());
        $this->assertSame($expectedDocumentPrefix, $result->getDocumentPrefix());
        $this->assertSame($expectedIsPreview, $result->isPreview());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertSame($expectedPageTitle, $result->getPageTitle());
        $this->assertSame($publicationDate, $result->getPublicationDate());
        $this->assertSame($expectedMainDepartment, $result->getMainDepartment());
        $this->assertSame($expectedSubject, $result->getSubject());
        $this->assertTrue($result->hasSubject());
        $this->assertSame($expectedSummary, $result->getSummary());
        $this->assertSame($expectedType, $result->getType());
        $this->assertEquals($expectedDocumentSearchUrl, $result->documentSearchUrl);
    }

    public function testMakeWithWooDecisionInPreview(): void
    {
        $department = Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $mainDocument = Mockery::mock(WooDecisionMainDocument::class);

        $expectedDepartment = new Department(
            name: 'my department',
            feedbackContent: null,
            responsibilityContent: null,
        );
        $this->departmentViewFactory
            ->expects('makeCollection')
            ->with($departments)
            ->andReturn(new ArrayCollection([$expectedDepartment]));

        $this->commonDossierPropertiesViewFactory
            ->expects('make')
            ->andReturn(new CommonDossierProperties(
                dossierId: $expectedUuid = 'my uuid',
                dossierNr: $expectedDossierNr = 'my dossier nr',
                documentPrefix: $expectedDocumentPrefix = 'my document prefix',
                isPreview: $expectedIsPreview = true,
                title: $expectedTitle = 'my title',
                pageTitle: $expectedPageTitle = 'my page title',
                publicationDate: $publicationDate = new DateTimeImmutable(),
                mainDepartment: $expectedMainDepartment = new Department(
                    name: DepartmentEnum::JV->value,
                    feedbackContent: null,
                    responsibilityContent: null,
                ),
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::COVENANT,
                subject: $expectedSubject = Mockery::mock(SubjectViewModel::class),
            ));

        $isInventoryRequired = true;
        $isInventoryOptional = false;
        $canProvideInventory = true;
        $decisionDate = $expectedDecisionDate = $this->getRandomDate();
        $decisionDocument = $mainDocument;
        $dateFrom = null;
        $dateTo = $expectedDateTo = $this->getRandomDate();

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDossierNr')->andReturn('my dossier nr');
        $dossier->expects('getDocumentPrefix')->andReturn('my document prefix');
        $dossier->expects('getDepartments')->andReturn($departments);
        $dossier->expects('isInventoryRequired')->andReturn($isInventoryRequired);
        $dossier->expects('isInventoryOptional')->andReturn($isInventoryOptional);
        $dossier->expects('canProvideInventory')->andReturn($canProvideInventory);
        $dossier->expects('getDecision')->andReturn(DecisionType::NOT_PUBLIC);
        $dossier->expects('getDecisionDate')->andReturn($decisionDate);
        $dossier->expects('getMainDocument')->andReturn($decisionDocument);
        $dossier->expects('getDateFrom')->andReturn($dateFrom);
        $dossier->expects('getDateTo')->andReturn($dateTo);
        $dossier->expects('getPublicationReason')->andReturn(PublicationReason::WOO_REQUEST);

        $this->mainDocumentViewFactory
            ->expects('make')
            ->with($dossier, $mainDocument)
            ->andReturn($expectedMainDocumentView = Mockery::mock(MainDocument::class));

        $this->router
            ->expects('generate')
            ->with('app_search', ['dnr' => ['my document prefix|my dossier nr']])
            ->andReturn($expectedDocumentSearchUrl = '/foo/var');

        $result = $this->factory->make($dossier);

        $this->assertInstanceOf(DossierCounts::class, $result->counts);
        $this->assertSame($isInventoryRequired, $result->isInventoryRequired);
        $this->assertSame($isInventoryOptional, $result->isInventoryOptional);
        $this->assertSame($canProvideInventory, $result->canProvideInventory);
        $this->assertSame(DecisionType::NOT_PUBLIC, $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($expectedMainDocumentView, $result->mainDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame(PublicationReason::WOO_REQUEST, $result->publicationReason);
        $this->assertTrue($result->hasSubject());
        $this->assertSame($expectedUuid, $result->getDossierId());
        $this->assertSame($expectedDossierNr, $result->getDossierNr());
        $this->assertSame($expectedDocumentPrefix, $result->getDocumentPrefix());
        $this->assertSame($expectedIsPreview, $result->isPreview());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertSame($expectedPageTitle, $result->getPageTitle());
        $this->assertEquals($publicationDate, $result->getPublicationDate());
        $this->assertSame($expectedMainDepartment, $result->getMainDepartment());
        $this->assertSame($expectedSubject, $result->getSubject());
        $this->assertTrue($result->hasSubject());
        $this->assertSame($expectedSummary, $result->getSummary());
        $this->assertSame($expectedType, $result->getType());
        $this->assertEquals($expectedDocumentSearchUrl, $result->documentSearchUrl);
    }

    private function getRandomDate(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween('-2 years'));
    }
}
