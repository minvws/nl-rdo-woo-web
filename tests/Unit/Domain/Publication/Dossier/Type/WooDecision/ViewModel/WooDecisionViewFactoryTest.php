<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierPropertiesViewFactory;
use App\Domain\Publication\Dossier\Type\ViewModel\Subject as SubjectViewModel;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecisionMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Domain\Publication\Subject\Subject;
use App\Entity\Department as DepartmentEntity;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
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
            ->andReturn($expectedDepartment = new Department(DepartmentEnum::VWS->value));

        $this->departmentViewFactory
            ->shouldReceive('makeCollection')
            ->with($departments)
            ->andReturn(new ArrayCollection([$expectedDepartment]));

        $dossier = $this->createWooDecision(
            status: DossierStatus::PUBLISHED,
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
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
                mainDepartment: $expectedMainDepartment = new Department(DepartmentEnum::VWS->value),
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
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame(DecisionType::NOT_PUBLIC, $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($expectedMainDocumentView, $result->mainDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame(PublicationReason::WOO_REQUEST, $result->publicationReason);
        $this->assertFalse($result->isExternalDepartmentResponsible());
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

    public function testMakeWithWooDecisionInPreviewWithNonVwsDepartment(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $mainDocument = \Mockery::mock(WooDecisionMainDocument::class);

        $this->departmentViewFactory->shouldReceive('make')->with($department)->andReturn($expectedDepartment = new Department('my departmen'));
        $this->departmentViewFactory->shouldReceive('makeCollection')->with($departments)->andReturn(new ArrayCollection([$expectedDepartment]));

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
                mainDepartment: $expectedMainDepartment = new Department(DepartmentEnum::JV->value),
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::COVENANT,
                subject: $expectedSubject = \Mockery::mock(SubjectViewModel::class),
            ));

        $dossier = $this->createWooDecision(
            status: DossierStatus::PREVIEW,
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
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
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame(DecisionType::NOT_PUBLIC, $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($expectedMainDocumentView, $result->mainDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame(PublicationReason::WOO_REQUEST, $result->publicationReason);
        $this->assertTrue($result->isExternalDepartmentResponsible());
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
        bool $needsInventoryAndDocuments,
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
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturn($needsInventoryAndDocuments);
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
