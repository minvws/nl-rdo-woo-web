<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\PublicationItem;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Webmozart\Assert\Assert;

final class WooDecisionTest extends UnitTestCase
{
    public function testIsVwsResponsibleReturnsTrueWithOneVwsDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(true);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDecision($departments, subject: null);

        $this->assertTrue($wooDecision->isVwsResponsible());
    }

    public function testIsVwsResponsibleReturnsFalseWithOneNonVwsDepartment(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(false);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDecision($departments, subject: null);

        $this->assertFalse($wooDecision->isVwsResponsible());
    }

    public function testIsVwsResponsibleReturnsFalseWithMultipleDepartments(): void
    {
        $departmentOne = \Mockery::mock(Department::class);
        $departmentTwo = \Mockery::mock(Department::class);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$departmentOne, $departmentTwo]);

        $wooDecision = $this->getWooDecision($departments, subject: null);

        $this->assertFalse($wooDecision->isVwsResponsible());
    }

    public function testHasSubjectReturnsTrue(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(true);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDecision($departments, $expectedSubject = 'my subject');

        $this->assertTrue($wooDecision->hasSubject());
        $this->assertSame($expectedSubject, $wooDecision->subject);
    }

    public function testHasSubjectReturnsFalse(): void
    {
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('isDepartment')->with(DepartmentEnum::VWS)->andReturn(true);

        /** @var ArrayCollection<array-key,Department> $departments */
        $departments = new ArrayCollection([$department]);

        $wooDecision = $this->getWooDecision($departments, subject: null);

        $this->assertFalse($wooDecision->hasSubject());
        $this->assertSame(null, $wooDecision->subject);
    }

    /**
     * @param ArrayCollection<array-key,Department> $departments
     */
    private function getWooDecision(ArrayCollection $departments, ?string $subject): WooDecision
    {
        $dossierCounts = \Mockery::mock(DossierCounts::class);
        $decisionDocument = \Mockery::mock(PublicationItem::class);

        $department = $departments->first();
        Assert::notFalse($department);

        return new WooDecision(
            counts: $dossierCounts,
            dossierId: 'dossierId',
            dossierNr: 'dossierNr',
            documentPrefix: 'documentPrefix',
            isPreview: true,
            title: 'title',
            pageTitle: 'pageTitle',
            publicationDate: new \DateTimeImmutable(),
            mainDepartment: $department,
            departments: $departments,
            summary: 'summary',
            needsInventoryAndDocuments: true,
            decision: DecisionType::PUBLIC,
            decisionDate: new \DateTimeImmutable(),
            decisionDocument: $decisionDocument,
            dateFrom: new \DateTimeImmutable(),
            dateTo: new \DateTimeImmutable(),
            publicationReason: PublicationReason::WOO_REQUEST,
            subject: $subject,
        );
    }
}
