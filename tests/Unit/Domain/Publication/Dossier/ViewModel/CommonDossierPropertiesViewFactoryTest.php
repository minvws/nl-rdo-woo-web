<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department as DepartmentEntity;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\Department;
use Shared\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\Subject as SubjectViewModel;
use Shared\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use Shared\Tests\Story\DepartmentEnum;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class CommonDossierPropertiesViewFactoryTest extends UnitTestCase
{
    private DepartmentViewFactory&MockInterface $departmentViewFactory;
    private SubjectViewFactory&MockInterface $subjectViewFactory;
    private CommonDossierPropertiesViewFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departmentViewFactory = Mockery::mock(DepartmentViewFactory::class);
        $this->subjectViewFactory = Mockery::mock(SubjectViewFactory::class);

        $this->factory = new CommonDossierPropertiesViewFactory(
            $this->departmentViewFactory,
            $this->subjectViewFactory,
        );
    }

    public function testMake(): void
    {
        $uuid = Mockery::mock(Uuid::class);
        $uuid->expects('toRfc4122')->andReturn($expectedUuid = 'my uuid');

        $department = Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $this->departmentViewFactory
            ->expects('make')
            ->with($department)
            ->andReturn($expectedMainDepartment = new Department(
                name: DepartmentEnum::VWS->value,
                feedbackContent: null,
                responsibilityContent: null,
            ));

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($uuid);
        $dossier->expects('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $dossier->expects('getDocumentPrefix')->andReturn($expectedDocumentPrefix = 'my document prefix');
        $dossier->expects('getStatus')->times(2)->andReturn($expectedStatus = DossierStatus::PUBLISHED);
        $dossier->expects('getTitle')->andReturn($expectedTitle = 'my title');
        $dossier->expects('getPublicationDate')->andReturn($expectedPublicationDate = $this->getFaker()->plainDateBetween('-2 years'));
        $dossier->expects('getDepartments')->andReturn($departments);
        $dossier->expects('getSummary')->andReturn($expectedSummary = 'my summary');
        $dossier->expects('getType')->andReturn($expectedType = DossierType::COVENANT);

        $this->subjectViewFactory
            ->expects('getSubjectForDossier')
            ->with($dossier)
            ->andReturn($expectedSubject = Mockery::mock(SubjectViewModel::class));

        $result = $this->factory->make($dossier);

        $this->assertSame($expectedUuid, $result->dossierId);
        $this->assertSame($expectedDossierNr, $result->dossierNr);
        $this->assertSame($expectedDocumentPrefix, $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame($expectedTitle, $result->title);
        $this->assertSame($expectedTitle, $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($expectedMainDepartment, $result->mainDepartment);
        $this->assertSame($expectedSummary, $result->summary);
        $this->assertSame($expectedType, $result->type);
        $this->assertSame($expectedSubject, $result->subject);
    }

    public function testMakeForPreview(): void
    {
        $uuid = Mockery::mock(Uuid::class);
        $uuid->expects('toRfc4122')->andReturn($expectedUuid = 'my uuid');

        $department = Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        $expectedMainDepartment = new Department(
            name: DepartmentEnum::VWS->value,
            feedbackContent: null,
            responsibilityContent: null,
        );
        $this->departmentViewFactory
            ->expects('make')
            ->with($department)
            ->andReturn($expectedMainDepartment);

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($uuid);
        $dossier->expects('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $dossier->expects('getDocumentPrefix')->andReturn($expectedDocumentPrefix = 'my document prefix');
        $dossier->expects('getStatus')->times(2)->andReturn($expectedStatus = DossierStatus::PREVIEW);
        $dossier->expects('getTitle')->andReturn($expectedTitle = 'my title');
        $dossier->expects('getPublicationDate')->andReturn($expectedPublicationDate = $this->getFaker()->plainDateBetween('-2 years'));
        $dossier->expects('getDepartments')->andReturn($departments);
        $dossier->expects('getSummary')->andReturn($expectedSummary = 'my summary');
        $dossier->expects('getType')->andReturn($expectedType = DossierType::COVENANT);

        $this->subjectViewFactory
            ->expects('getSubjectForDossier')
            ->with($dossier)
            ->andReturn($expectedSubject = Mockery::mock(SubjectViewModel::class));

        $result = $this->factory->make($dossier);

        $this->assertSame($expectedUuid, $result->dossierId);
        $this->assertSame($expectedDossierNr, $result->dossierNr);
        $this->assertSame($expectedDocumentPrefix, $result->documentPrefix);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame($expectedTitle, $result->title);
        $this->assertSame($expectedTitle . ' (preview)', $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($expectedMainDepartment, $result->mainDepartment);
        $this->assertSame($expectedSummary, $result->summary);
        $this->assertSame($expectedType, $result->type);
        $this->assertSame($expectedSubject, $result->subject);
    }
}
