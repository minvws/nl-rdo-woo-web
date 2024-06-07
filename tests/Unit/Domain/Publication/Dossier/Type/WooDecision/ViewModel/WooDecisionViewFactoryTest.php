<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecisionViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Domain\Publication\Dossier\ViewModel\PublicationItem;
use App\Domain\Publication\Dossier\ViewModel\PublicationItemViewFactory;
use App\Entity\DecisionDocument;
use App\Entity\Department as DepartmentEntity;
use App\Enum\Department as DepartmentEnum;
use App\Repository\WooDecisionRepository;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class WooDecisionViewFactoryTest extends UnitTestCase
{
    private WooDecisionRepository&MockInterface $dossierRepository;
    private DepartmentViewFactory&MockInterface $departmentViewFactory;
    private PublicationItemViewFactory&MockInterface $publicationItemViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossierRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->dossierRepository->shouldReceive('getDossierCounts')->andReturn(\Mockery::mock(DossierCounts::class));

        $this->departmentViewFactory = \Mockery::mock(DepartmentViewFactory::class);

        $this->publicationItemViewFactory = \Mockery::mock(PublicationItemViewFactory::class);
    }

    public function testMake(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $decisionDocument = \Mockery::mock(DecisionDocument::class);

        $this->departmentViewFactory
            ->shouldReceive('make')
            ->with($department)
            ->andReturn($expectedDepartment = new Department(DepartmentEnum::VWS->value));

        $this->departmentViewFactory
            ->shouldReceive('makeCollection')
            ->with($departments)
            ->andReturn(new ArrayCollection([$expectedDepartment]));

        $this->publicationItemViewFactory
            ->shouldReceive('make')
            ->with($decisionDocument)
            ->andReturn($expectedDecisionDocument = new PublicationItem('my filename', 100, true));

        $dossier = $this->createWooDecision(
            status: $expectedStatus = DossierStatus::PUBLISHED,
            publicationDate: $expectedPublicationDate = $this->getRandomDate(),
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            decisionDocument: $decisionDocument,
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $result = (new WooDecisionViewFactory(
            $this->dossierRepository,
            $this->departmentViewFactory,
            $this->publicationItemViewFactory,
        ))->make($dossier);

        $this->assertInstanceOf(DossierCounts::class, $result->counts);
        $this->assertSame('my uuid', $result->dossierId);
        $this->assertSame('my dossier nr', $result->dossierNr);
        $this->assertSame('my document prefix', $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame('my title', $result->title);
        $this->assertSame('my title', $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($expectedDepartment, $result->mainDepartment);
        $this->assertSame('my summary', $result->summary);
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame(DecisionType::NOT_PUBLIC, $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($expectedDecisionDocument, $result->decisionDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame(PublicationReason::WOO_REQUEST, $result->publicationReason);
        $this->assertTrue($result->isVwsResponsible());
    }

    public function testMakeWithWooDecisionInPreviewWithNonVwsDepartment(): void
    {
        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $decisionDocument = \Mockery::mock(DecisionDocument::class);

        $this->departmentViewFactory->shouldReceive('make')->with($department)->andReturn($expectedDepartment = new Department('my departmen'));
        $this->departmentViewFactory->shouldReceive('makeCollection')->with($departments)->andReturn(new ArrayCollection([$expectedDepartment]));
        $this->publicationItemViewFactory->shouldReceive('make')->with($decisionDocument)->andReturn($expectedDecisionDocument = new PublicationItem('my filename', 100, true));

        $dossier = $this->createWooDecision(
            status: $expectedStatus = DossierStatus::PREVIEW,
            publicationDate: $expectedPublicationDate = $this->getRandomDate(),
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            decisionDocument: $decisionDocument,
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $result = (new WooDecisionViewFactory(
            $this->dossierRepository,
            $this->departmentViewFactory,
            $this->publicationItemViewFactory,
        ))->make($dossier);

        $this->assertInstanceOf(DossierCounts::class, $result->counts);
        $this->assertSame('my uuid', $result->dossierId);
        $this->assertSame('my dossier nr', $result->dossierNr);
        $this->assertSame('my document prefix', $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame('my title', $result->title);
        $this->assertSame(sprintf('%s %s', 'my title', '(preview)'), $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($expectedDepartment, $result->mainDepartment);
        $this->assertSame('my summary', $result->summary);
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame(DecisionType::NOT_PUBLIC, $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($expectedDecisionDocument, $result->decisionDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame(PublicationReason::WOO_REQUEST, $result->publicationReason);
        $this->assertFalse($result->isVwsResponsible());
    }

    /**
     * @param ArrayCollection<array-key,DepartmentEntity> $departments
     */
    private function createWooDecision(
        DossierStatus $status,
        \DateTimeImmutable $publicationDate,
        ArrayCollection $departments,
        bool $needsInventoryAndDocuments,
        \DateTimeImmutable $decisionDate,
        DecisionDocument $decisionDocument,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
    ): WooDecision {
        $uuid = \Mockery::mock(Uuid::class);
        $uuid->shouldReceive('toRfc4122')->andReturn('my uuid');

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($uuid);
        $dossier->shouldReceive('getDossierNr')->andReturn('my dossier nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('my document prefix');
        $dossier->shouldReceive('getStatus')->andReturn($status);
        $dossier->shouldReceive('getTitle')->andReturn('my title');
        $dossier->shouldReceive('getPublicationDate')->andReturn($publicationDate);
        $dossier->shouldReceive('getDepartments')->andReturn($departments);
        $dossier->shouldReceive('getSummary')->andReturn('my summary');
        $dossier->shouldReceive('needsInventoryAndDocuments')->andReturn($needsInventoryAndDocuments);
        $dossier->shouldReceive('getDecision')->andReturn(DecisionType::NOT_PUBLIC);
        $dossier->shouldReceive('getDecisionDate')->andReturn($decisionDate);
        $dossier->shouldReceive('getDecisionDocument')->andReturn($decisionDocument);
        $dossier->shouldReceive('getDateFrom')->andReturn($dateFrom);
        $dossier->shouldReceive('getDateTo')->andReturn($dateTo);
        $dossier->shouldReceive('getPublicationReason')->andReturn(PublicationReason::WOO_REQUEST);

        return $dossier;
    }

    private function getRandomDate(string $startDate = '-2 years'): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween($startDate));
    }
}
