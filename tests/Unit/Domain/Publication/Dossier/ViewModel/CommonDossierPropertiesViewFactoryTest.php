<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Department\Department as DepartmentEntity;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Domain\Publication\Dossier\ViewModel\Subject as SubjectViewModel;
use App\Domain\Publication\Dossier\ViewModel\SubjectViewFactory;
use App\Domain\Publication\Subject\Subject;
use App\Tests\Story\DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class CommonDossierPropertiesViewFactoryTest extends UnitTestCase
{
    private DepartmentViewFactory&MockInterface $departmentViewFactory;
    private SubjectViewFactory&MockInterface $subjectViewFactory;
    private CommonDossierPropertiesViewFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departmentViewFactory = \Mockery::mock(DepartmentViewFactory::class);
        $this->subjectViewFactory = \Mockery::mock(SubjectViewFactory::class);

        $this->factory = new CommonDossierPropertiesViewFactory(
            $this->departmentViewFactory,
            $this->subjectViewFactory,
        );
    }

    public function testMake(): void
    {
        $uuid = \Mockery::mock(Uuid::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my uuid');

        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        /** @var Subject&MockInterface $subject */
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getName')->andReturn($expectedSubject = 'A subject');

        $this->departmentViewFactory
            ->shouldReceive('make')
            ->with($department)
            ->andReturn($expectedMainDepartment = new Department(DepartmentEnum::VWS->value, feedbackContent: null));

        /** @var AbstractDossier&MockInterface $dossier */
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId')->andReturn($uuid);
        $dossier->shouldReceive('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix = 'my document prefix');
        $dossier->shouldReceive('getStatus')->andReturn($expectedStatus = DossierStatus::PUBLISHED);
        $dossier->shouldReceive('getTitle')->andReturn($expectedTitle = 'my title');
        $dossier->shouldReceive('getPublicationDate')->andReturn(
            $expectedPublicationDate = \DateTimeImmutable::createFromInterface(
                $this->getFaker()->dateTimeBetween('-2 years')
            )
        );
        $dossier->shouldReceive('getDepartments')->andReturn($departments);
        $dossier->shouldReceive('getSummary')->andReturn($expectedSummary = 'my summary');
        $dossier->shouldReceive('getType')->andReturn($expectedType = DossierType::COVENANT);
        $dossier->shouldReceive('getSubject')->andReturn($subject);

        $this->subjectViewFactory
            ->shouldReceive('getSubjectForDossier')
            ->with($dossier)
            ->andReturn($expectedSubject = \Mockery::mock(SubjectViewModel::class));

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
        $uuid = \Mockery::mock(Uuid::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my uuid');

        $department = \Mockery::mock(DepartmentEntity::class);
        /** @var ArrayCollection<array-key,DepartmentEntity> $departments */
        $departments = new ArrayCollection([$department]);

        /** @var Subject&MockInterface $subject */
        $subject = \Mockery::mock(Subject::class);
        $subject->shouldReceive('getName')->andReturn($expectedSubject = 'A subject');

        $this->departmentViewFactory
            ->shouldReceive('make')
            ->with($department)
            ->andReturn($expectedMainDepartment = new Department(DepartmentEnum::VWS->value, feedbackContent: null));

        /** @var AbstractDossier&MockInterface $dossier */
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId')->andReturn($uuid);
        $dossier->shouldReceive('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix = 'my document prefix');
        $dossier->shouldReceive('getStatus')->andReturn($expectedStatus = DossierStatus::PREVIEW);
        $dossier->shouldReceive('getTitle')->andReturn($expectedTitle = 'my title');
        $dossier->shouldReceive('getPublicationDate')->andReturn(
            $expectedPublicationDate = \DateTimeImmutable::createFromInterface(
                $this->getFaker()->dateTimeBetween('-2 years')
            )
        );
        $dossier->shouldReceive('getDepartments')->andReturn($departments);
        $dossier->shouldReceive('getSummary')->andReturn($expectedSummary = 'my summary');
        $dossier->shouldReceive('getType')->andReturn($expectedType = DossierType::COVENANT);
        $dossier->shouldReceive('getSubject')->andReturn($subject);

        $this->subjectViewFactory
            ->shouldReceive('getSubjectForDossier')
            ->with($dossier)
            ->andReturn($expectedSubject = \Mockery::mock(SubjectViewModel::class));

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
