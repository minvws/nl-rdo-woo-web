<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel\InvestigationReportViewFactory;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Entity\Department as DepartmentEntity;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class InvestigationReportViewFactoryTest extends UnitTestCase
{
    private DepartmentViewFactory&MockInterface $departmentViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departmentViewFactory = \Mockery::mock(DepartmentViewFactory::class);
    }

    public function testMake(): void
    {
        $uuid = \Mockery::mock(Uuid::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my uuid');

        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $this->departmentViewFactory
            ->shouldReceive('make')
            ->with($department)
            ->andReturn($expectedMainDepartment = new Department(DepartmentEnum::VWS->value));

        $dossier = \Mockery::mock(InvestigationReport::class);
        $dossier->shouldReceive('getId')->andReturn($uuid);
        $dossier->shouldReceive('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix = 'my document prefix');
        $dossier->shouldReceive('getStatus')->andReturn($expectedStatus = DossierStatus::PUBLISHED);
        $dossier->shouldReceive('getTitle')->andReturn($expectedTitle = 'my title');
        $dossier->shouldReceive('getDepartments')->andReturn($departments);
        $dossier->shouldReceive('getSummary')->andReturn($expectedSummary = 'my summary');
        $dossier->shouldReceive('getType')->andReturn($expectedType = DossierType::INVESTIGATION_REPORT);
        $dossier->shouldReceive('getDateFrom')->andReturn($expectedDate = new \DateTimeImmutable());
        $dossier->shouldReceive('getPublicationDate')->andReturn($publicationDate = new \DateTimeImmutable());

        $result = (new InvestigationReportViewFactory($this->departmentViewFactory))->make($dossier);

        $this->assertSame($expectedUuid, $result->dossierId);
        $this->assertSame($expectedDossierNr, $result->dossierNr);
        $this->assertSame($expectedDocumentPrefix, $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame($expectedTitle, $result->title);
        $this->assertSame($expectedTitle, $result->pageTitle);
        $this->assertSame($expectedDate, $result->date);
        $this->assertSame($expectedMainDepartment, $result->mainDepartment);
        $this->assertSame($expectedSummary, $result->summary);
        $this->assertSame($expectedType, $result->type);
        $this->assertSame($publicationDate, $result->publicationDate);
    }
}
