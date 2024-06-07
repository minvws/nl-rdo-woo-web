<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\ViewModel\CovenantViewFactory;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Entity\Department as DepartmentEntity;
use App\Enum\Department as DepartmentEnum;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class CovenantViewFactoryTest extends UnitTestCase
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

        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($uuid);
        $covenant->shouldReceive('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $covenant->shouldReceive('getDocumentPrefix')->andReturn($expectedDocumentPrefix = 'my document prefix');
        $covenant->shouldReceive('getStatus')->andReturn($expectedStatus = DossierStatus::PUBLISHED);
        $covenant->shouldReceive('getTitle')->andReturn($expectedTitle = 'my title');
        $covenant->shouldReceive('getPublicationDate')->andReturn($expectedPublicationDate = \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween('-2 years')));
        $covenant->shouldReceive('getDepartments')->andReturn($departments);
        $covenant->shouldReceive('getSummary')->andReturn($expectedSummary = 'my summary');
        $covenant->shouldReceive('getType')->andReturn($expectedType = DossierType::COVENANT);
        $covenant->shouldReceive('getDateFrom')->andReturn($expectedDateFrom = null);
        $covenant->shouldReceive('getDateTo')->andReturn($expecedDateTo = $this->getRandomDate());
        $covenant->shouldReceive('getPreviousVersionLink')->andReturn($expectedPreviousVersionLink = 'my previous version link');
        $covenant->shouldReceive('getParties')->andReturn($expectedParties = ['part one', 'party rwo']);

        $result = (new CovenantViewFactory($this->departmentViewFactory))->make($covenant);

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
        $this->assertSame($expectedDateFrom, $result->dateFrom);
        $this->assertSame($expecedDateTo, $result->dateTo);
        $this->assertSame($expectedPreviousVersionLink, $result->previousVersionLink);
        $this->assertSame($expectedParties, $result->parties);
    }

    private function getRandomDate(string $startDate = '-2 years'): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween($startDate));
    }
}
