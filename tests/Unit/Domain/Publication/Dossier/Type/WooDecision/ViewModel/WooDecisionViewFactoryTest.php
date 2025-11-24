<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Shared\Domain\Department\Department as DepartmentEntity;
use Shared\Domain\Publication\Dossier\DossierStatus;
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
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Story\DepartmentEnum;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

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

        $this->dossierRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->dossierRepository->shouldReceive('getDossierCounts')->andReturn(\Mockery::mock(DossierCounts::class));

        $this->departmentViewFactory = \Mockery::mock(DepartmentViewFactory::class);
        $this->mainDocumentViewFactory = \Mockery::mock(MainDocumentViewFactory::class);
        $this->commonDossierPropertiesViewFactory = \Mockery::mock(CommonDossierPropertiesViewFactory::class);
        $this->router = \Mockery::mock(RouterInterface::class);

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
        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $mainDocument = \Mockery::mock(WooDecisionMainDocument::class);

        $this->departmentViewFactory
            ->shouldReceive('make')
            ->with($department)
            ->andReturn($expectedDepartment = new Department(
                DepartmentEnum::VWS->value,
                feedbackContent: null,
                responsibilityContent: null,
            ));

        $this->departmentViewFactory
            ->shouldReceive('makeCollection')
            ->with($departments)
            ->andReturn(new ArrayCollection([$expectedDepartment]));

        $dossier = $this->createWooDecision(
            status: DossierStatus::PUBLISHED,
            departments: $departments,
            isInventoryRequired: $expectedisInventoryRequired = true,
            isInventoryOptional: $expectedisInventoryOptional = false,
            canProvideInventory: $expectedcanProvideInventory = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            decisionDocument: $mainDocument,
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $this->mainDocumentViewFactory
            ->shouldReceive('make')
            ->with($dossier, $mainDocument)
            ->andReturn($expectedMainDocumentView = \Mockery::mock(MainDocument::class));

        $this->commonDossierPropertiesViewFactory
            ->shouldReceive('make')
            ->andReturn(new CommonDossierProperties(
                dossierId: $expectedUuid = 'my uuid',
                dossierNr: $expectedDossierNr = 'my dossier nr',
                documentPrefix: $expectedDocumentPrefix = 'my document prefix',
                isPreview: $expectedIsPreview = true,
                title: $expectedTitle = 'my title',
                pageTitle: $expectedPageTitle = 'my page title',
                publicationDate: $publicationDate = new \DateTimeImmutable(),
                mainDepartment: $expectedMainDepartment = new Department(
                    name: DepartmentEnum::VWS->value,
                    feedbackContent: null,
                    responsibilityContent: null,
                ),
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::COVENANT,
                subject: $expectedSubject = \Mockery::mock(SubjectViewModel::class),
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
        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $mainDocument = \Mockery::mock(WooDecisionMainDocument::class);

        $this->departmentViewFactory
            ->shouldReceive('make')
            ->with($department)
            ->andReturn($expectedDepartment = new Department(
                name: 'my department',
                feedbackContent: null,
                responsibilityContent: null,
            ));
        $this->departmentViewFactory
            ->shouldReceive('makeCollection')
            ->with($departments)
            ->andReturn(new ArrayCollection([$expectedDepartment]));

        $this->commonDossierPropertiesViewFactory
            ->shouldReceive('make')
            ->andReturn(new CommonDossierProperties(
                dossierId: $expectedUuid = 'my uuid',
                dossierNr: $expectedDossierNr = 'my dossier nr',
                documentPrefix: $expectedDocumentPrefix = 'my document prefix',
                isPreview: $expectedIsPreview = true,
                title: $expectedTitle = 'my title',
                pageTitle: $expectedPageTitle = 'my page title',
                publicationDate: $publicationDate = new \DateTimeImmutable(),
                mainDepartment: $expectedMainDepartment = new Department(
                    name: DepartmentEnum::JV->value,
                    feedbackContent: null,
                    responsibilityContent: null,
                ),
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::COVENANT,
                subject: $expectedSubject = \Mockery::mock(SubjectViewModel::class),
            ));

        $dossier = $this->createWooDecision(
            status: DossierStatus::PREVIEW,
            departments: $departments,
            isInventoryRequired: $expectedisInventoryRequired = true,
            isInventoryOptional: $expectedisInventoryOptional = false,
            canProvideInventory: $expectedcanProvideInventory = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            decisionDocument: $mainDocument,
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $this->mainDocumentViewFactory
            ->shouldReceive('make')
            ->with($dossier, $mainDocument)
            ->andReturn($expectedMainDocumentView = \Mockery::mock(MainDocument::class));

        $this->router
            ->expects('generate')
            ->with('app_search', ['dnr' => ['my document prefix|my dossier nr']])
            ->andReturn($expectedDocumentSearchUrl = '/foo/var');

        $result = $this->factory->make($dossier);

        $this->assertInstanceOf(DossierCounts::class, $result->counts);
        $this->assertSame($expectedisInventoryRequired, $result->isInventoryRequired);
        $this->assertSame($expectedisInventoryOptional, $result->isInventoryOptional);
        $this->assertSame($expectedcanProvideInventory, $result->canProvideInventory);
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

    /**
     * @param ArrayCollection<array-key,DepartmentEntity> $departments
     */
    private function createWooDecision(
        DossierStatus $status,
        ArrayCollection $departments,
        bool $isInventoryRequired,
        bool $isInventoryOptional,
        bool $canProvideInventory,
        \DateTimeImmutable $decisionDate,
        WooDecisionMainDocument $decisionDocument,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
    ): WooDecision {
        $uuid = \Mockery::mock(Uuid::class);
        $uuid->shouldReceive('toRfc4122')->andReturn('my uuid');

        /** @var Subject&MockInterface $subject */
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getName')->andReturn('my subject');

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($uuid);
        $dossier->shouldReceive('getDossierNr')->andReturn('my dossier nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('my document prefix');
        $dossier->shouldReceive('getStatus')->andReturn($status);
        $dossier->shouldReceive('getTitle')->andReturn('my title');
        $dossier->shouldReceive('getDepartments')->andReturn($departments);
        $dossier->shouldReceive('getSummary')->andReturn('my summary');
        $dossier->shouldReceive('isInventoryRequired')->andReturn($isInventoryRequired);
        $dossier->shouldReceive('isInventoryOptional')->andReturn($isInventoryOptional);
        $dossier->shouldReceive('canProvideInventory')->andReturn($canProvideInventory);
        $dossier->shouldReceive('getDecision')->andReturn(DecisionType::NOT_PUBLIC);
        $dossier->shouldReceive('getDecisionDate')->andReturn($decisionDate);
        $dossier->shouldReceive('getMainDocument')->andReturn($decisionDocument);
        $dossier->shouldReceive('getDateFrom')->andReturn($dateFrom);
        $dossier->shouldReceive('getDateTo')->andReturn($dateTo);
        $dossier->shouldReceive('getPublicationReason')->andReturn(PublicationReason::WOO_REQUEST);
        $dossier->shouldReceive('getSubject')->andReturn($subject);

        return $dossier;
    }

    private function getRandomDate(string $startDate = '-2 years'): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween($startDate));
    }
}
