<?php

declare(strict_types=1);

namespace App\Tests\Unit\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision as WooDecisionEntity;
use App\Entity\DecisionDocument;
use App\Entity\Department;
use App\Entity\Inventory;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use App\ViewModel\DossierCounts;
use App\ViewModel\WooDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Webmozart\Assert\Assert;

final class WooDecisionTest extends UnitTestCase
{
    public function testIsVwsResponsibleReturnsTrueWithOneVwsDeparment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn(DepartmentEnum::VWS->value);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDicision($departments);

        $this->assertTrue($wooDecision->isVwsResponsible());
    }

    public function testIsVwsResponsibleReturnsFalseWithOneNonVwsDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn(DepartmentEnum::JV->value);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDicision($departments);

        $this->assertFalse($wooDecision->isVwsResponsible());
    }

    public function testIsVwsResponsibleReturnsFalseWithMultipleDepartments(): void
    {
        $departmentOne = \Mockery::mock(Department::class);
        $departmentOne->shouldReceive('getName')->andReturn(DepartmentEnum::VWS->value);

        $departmentTwo = \Mockery::mock(Department::class);
        $departmentTwo->shouldReceive('getName')->andReturn(DepartmentEnum::JV->value);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$departmentOne, $departmentTwo]);

        $wooDecision = $this->getWooDicision($departments);

        $this->assertFalse($wooDecision->isVwsResponsible());
    }

    /**
     * @param ArrayCollection<array-key,Department> $departments
     */
    private function getWooDicision(ArrayCollection $departments): WooDecision
    {
        $wooDicisionEntity = \Mockery::mock(WooDecisionEntity::class);
        $wooDicisionEntity->shouldReceive('getDepartments')->andReturn($departments);
        $dossierCounts = \Mockery::mock(DossierCounts::class);
        $inventory = \Mockery::mock(Inventory::class);
        $decisionDocument = \Mockery::mock(DecisionDocument::class);

        $department = $departments->first();
        Assert::notFalse($department);

        return new WooDecision(
            entity: $wooDicisionEntity,
            counts: $dossierCounts,
            dossierId: 'dossierId',
            dossierNr: 'dossierNr',
            documentPrefix: 'documentPrefix',
            isPreview: true,
            title: 'title',
            pageTitle: 'pageTitle',
            publicationDate: new \DateTimeImmutable(),
            mainDepartment: $department,
            summary: 'summary',
            needsInventoryAndDocuments: true,
            decision: 'decision',
            decisionDate: new \DateTimeImmutable(),
            inventory: $inventory,
            decisionDocument: $decisionDocument,
            dateFrom: new \DateTimeImmutable(),
            dateTo: new \DateTimeImmutable(),
            publicationReason: 'publicationReason',
        );
    }
}
