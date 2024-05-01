<?php

declare(strict_types=1);

namespace App\Tests\Unit\ViewModel\Factory;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\DecisionDocument;
use App\Entity\Department;
use App\Entity\Inventory;
use App\Enum\Department as DepartmentEnum;
use App\Repository\WooDecisionRepository;
use App\Tests\Unit\UnitTestCase;
use App\ViewModel\DossierCounts;
use App\ViewModel\Factory\WooDecisionViewFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

final class WooDecisionViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $dossierRepository = \Mockery::mock(WooDecisionRepository::class);
        $dossierRepository->shouldReceive('getDossierCounts')->andReturn($expectedIngested = \Mockery::mock(DossierCounts::class));

        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn(DepartmentEnum::VWS->value);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $dossier = $this->createWooDecision(
            status: $expectedStatus = DossierStatus::PUBLISHED,
            publicationDate: $expectedPublicationDate = $this->getRandomDate(),
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            inventory: $inventory = \Mockery::mock(Inventory::class),
            decisionDocument: $decisionDocument = \Mockery::mock(DecisionDocument::class),
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $result = (new WooDecisionViewFactory($dossierRepository))->make($dossier);

        $this->assertSame($expectedIngested, $result->counts);
        $this->assertSame('my uuid', $result->dossierId);
        $this->assertSame('my dossier nr', $result->dossierNr);
        $this->assertSame('my document prefix', $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame('my title', $result->title);
        $this->assertSame('my title', $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($department, $result->mainDepartment);
        $this->assertSame('my summary', $result->summary);
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame('my decision', $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($inventory, $result->inventory);
        $this->assertSame($decisionDocument, $result->decisionDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame('my publication reason', $result->publicationReason);
        $this->assertSame(true, $result->isVwsResponsible());
    }

    public function testMakeWithWooDecisionInPreviewWithNonVwsDepartment(): void
    {
        $dossierRepository = \Mockery::mock(WooDecisionRepository::class);
        $dossierRepository->shouldReceive('getDossierCounts')->andReturn($expectedIngested = \Mockery::mock(DossierCounts::class));

        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn('my department');

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $dossier = $this->createWooDecision(
            status: $expectedStatus = DossierStatus::PREVIEW,
            publicationDate: $expectedPublicationDate = $this->getRandomDate(),
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            inventory: $inventory = \Mockery::mock(Inventory::class),
            decisionDocument: $decisionDocument = \Mockery::mock(DecisionDocument::class),
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $result = (new WooDecisionViewFactory($dossierRepository))->make($dossier);

        $this->assertSame($expectedIngested, $result->counts);
        $this->assertSame('my uuid', $result->dossierId);
        $this->assertSame('my dossier nr', $result->dossierNr);
        $this->assertSame('my document prefix', $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame('my title', $result->title);
        $this->assertSame(sprintf('%s %s', 'my title', '(preview)'), $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($department, $result->mainDepartment);
        $this->assertSame('my summary', $result->summary);
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame('my decision', $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertSame($inventory, $result->inventory);
        $this->assertSame($decisionDocument, $result->decisionDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame('my publication reason', $result->publicationReason);
        $this->assertSame(false, $result->isVwsResponsible());
    }

    public function testMakeWithWooDecisionWithoutInventory(): void
    {
        $dossierRepository = \Mockery::mock(WooDecisionRepository::class);
        $dossierRepository->shouldReceive('getDossierCounts')->andReturn($expectedIngested = \Mockery::mock(DossierCounts::class));

        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn('my department');

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $dossier = $this->createWooDecision(
            status: $expectedStatus = DossierStatus::PREVIEW,
            publicationDate: $expectedPublicationDate = $this->getRandomDate(),
            departments: $departments,
            needsInventoryAndDocuments: $expectedNeedsInventoryAndDocuments = true,
            decisionDate: $expectedDecisionDate = $this->getRandomDate(),
            inventory: null,
            decisionDocument: $decisionDocument = \Mockery::mock(DecisionDocument::class),
            dateFrom: null,
            dateTo: $expectedDateTo = $this->getRandomDate(),
        );

        $result = (new WooDecisionViewFactory($dossierRepository))->make($dossier);

        $this->assertSame($expectedIngested, $result->counts);
        $this->assertSame('my uuid', $result->dossierId);
        $this->assertSame('my dossier nr', $result->dossierNr);
        $this->assertSame('my document prefix', $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame('my title', $result->title);
        $this->assertSame(sprintf('%s %s', 'my title', '(preview)'), $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($department, $result->mainDepartment);
        $this->assertSame('my summary', $result->summary);
        $this->assertSame($expectedNeedsInventoryAndDocuments, $result->needsInventoryAndDocuments);
        $this->assertSame('my decision', $result->decision);
        $this->assertSame($expectedDecisionDate, $result->decisionDate);
        $this->assertNull($result->inventory);
        $this->assertSame($decisionDocument, $result->decisionDocument);
        $this->assertNull($result->dateFrom);
        $this->assertSame($expectedDateTo, $result->dateTo);
        $this->assertSame('my publication reason', $result->publicationReason);
        $this->assertSame(false, $result->isVwsResponsible());
    }

    /**
     * @param ArrayCollection<array-key,Department> $departments
     */
    private function createWooDecision(
        DossierStatus $status,
        \DateTimeImmutable $publicationDate,
        ArrayCollection $departments,
        bool $needsInventoryAndDocuments,
        \DateTimeImmutable $decisionDate,
        ?Inventory $inventory,
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
        $dossier->shouldReceive('getDecision')->andReturn('my decision');
        $dossier->shouldReceive('getDecisionDate')->andReturn($decisionDate);
        $dossier->shouldReceive('getInventory')->andReturn($inventory);
        $dossier->shouldReceive('getDecisionDocument')->andReturn($decisionDocument);
        $dossier->shouldReceive('getDateFrom')->andReturn($dateFrom);
        $dossier->shouldReceive('getDateTo')->andReturn($dateTo);
        $dossier->shouldReceive('getPublicationReason')->andReturn('my publication reason');

        return $dossier;
    }

    private function getRandomDate(string $startDate = '-2 years'): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween($startDate));
    }
}
