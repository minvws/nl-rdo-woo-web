<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Webmozart\Assert\Assert;

final class WooDecisionTest extends UnitTestCase
{
    public function testIsExternalDepartmentResponsibleReturnsFalseWithOneVwsDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(true);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDecision($departments);

        $this->assertFalse($wooDecision->isExternalDepartmentResponsible());
    }

    public function testIsExternalDepartmentResponsibleReturnsTrueWithOneNonVwsDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(false);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDecision($departments);

        $this->assertTrue($wooDecision->isExternalDepartmentResponsible());
    }

    public function testIsExternalDepartmentResponsibleUsesFirstWithMultipleDepartments(): void
    {
        $departmentOne = \Mockery::mock(Department::class);
        $departmentOne->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(false);
        $departmentTwo = \Mockery::mock(Department::class);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$departmentOne, $departmentTwo]);

        $wooDecision = $this->getWooDecision($departments);

        $this->assertTrue($wooDecision->isExternalDepartmentResponsible());
    }

    /**
     * @param ArrayCollection<array-key,Department> $departments
     */
    private function getWooDecision(ArrayCollection $departments): WooDecision
    {
        $dossierCounts = \Mockery::mock(DossierCounts::class);
        $mainDocument = \Mockery::mock(MainDocument::class);

        $department = $departments->first();
        Assert::notFalse($department);

        return new WooDecision(
            new CommonDossierProperties(
                dossierId: 'dossierId',
                dossierNr: 'dossierNr',
                documentPrefix: 'documentPrefix',
                isPreview: true,
                title: 'title',
                pageTitle: 'pageTitle',
                publicationDate: new \DateTimeImmutable(),
                mainDepartment: $department,
                summary: 'summary',
                type: DossierType::WOO_DECISION,
                subject: null,
            ),
            counts: $dossierCounts,
            departments: $departments,
            needsInventoryAndDocuments: true,
            decision: DecisionType::PUBLIC,
            decisionDate: new \DateTimeImmutable(),
            mainDocument: $mainDocument,
            dateFrom: new \DateTimeImmutable(),
            dateTo: new \DateTimeImmutable(),
            publicationReason: PublicationReason::WOO_REQUEST,
            documentSearchUrl: '/foo/bar',
        );
    }
}
