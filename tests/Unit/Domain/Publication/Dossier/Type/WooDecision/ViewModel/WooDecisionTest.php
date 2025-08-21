<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DossierCounts;
use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\WooDecision;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Tests\Story\DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Webmozart\Assert\Assert;

final class WooDecisionTest extends UnitTestCase
{
    public function testGetResponsibilityContent(): void
    {
        $departmentOne = new Department(
            name: 'ministry of muggles',
            feedbackContent: null,
            responsibilityContent: null,
        );
        $departmentTwo = new Department(
            name: DepartmentEnum::VWS->value,
            feedbackContent: null,
            responsibilityContent: 'This is a responsibility content',
        );
        $departments = new ArrayCollection([$departmentOne, $departmentTwo]);

        $wooDecision = $this->getWooDecision($departments);

        $this->assertSame(
            'This is a responsibility content',
            $wooDecision->getResponsibilityContent(),
        );
    }

    public function testGetResponsibilityContentReturnsNullWhenNoDepartmentHasContent(): void
    {
        $departmentOne = new Department(
            name: 'ministry of muggles',
            feedbackContent: null,
            responsibilityContent: null,
        );
        $wooDecision = $this->getWooDecision(new ArrayCollection([$departmentOne]));

        $this->assertNull($wooDecision->getResponsibilityContent());
    }

    /**
     * @param ArrayCollection<array-key,Department> $departments
     */
    private function getWooDecision(ArrayCollection $departments): WooDecision
    {
        $dossierCounts = \Mockery::mock(DossierCounts::class);
        $mainDocument = \Mockery::mock(MainDocument::class);

        $mainDepartment = $departments->first();
        Assert::isInstanceOf($mainDepartment, Department::class);

        return new WooDecision(
            new CommonDossierProperties(
                dossierId: 'dossierId',
                dossierNr: 'dossierNr',
                documentPrefix: 'documentPrefix',
                isPreview: true,
                title: 'title',
                pageTitle: 'pageTitle',
                publicationDate: new \DateTimeImmutable(),
                mainDepartment: $mainDepartment,
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
