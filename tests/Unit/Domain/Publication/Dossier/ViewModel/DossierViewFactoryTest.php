<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\ViewModel\DossierViewFactory;
use Shared\Tests\Unit\UnitTestCase;

final class DossierViewFactoryTest extends UnitTestCase
{
    private DossierRepository&MockInterface $repository;
    private DossierViewFactory $factory;

    protected function setUp(): void
    {
        $this->repository = \Mockery::mock(DossierRepository::class);

        $this->factory = new DossierViewFactory(
            $this->repository,
        );

        parent::setUp();
    }

    public function testGetRecentDossiers(): void
    {
        $dossierA = \Mockery::mock(AbstractDossier::class);
        $dossierA->shouldReceive('getDossierNr')->andReturn('foo-123');
        $dossierA->shouldReceive('getDocumentPrefix')->andReturn('BAR');
        $dossierA->shouldReceive('getTitle')->andReturn('foo bar baz');
        $dossierA->shouldReceive('getType')->andReturn($typeA = DossierType::COVENANT);
        $dossierA->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());

        $dossierB = \Mockery::mock(AbstractDossier::class);
        $dossierB->shouldReceive('getDossierNr')->andReturn('foo-123');
        $dossierB->shouldReceive('getDocumentPrefix')->andReturn('BAR');
        $dossierB->shouldReceive('getTitle')->andReturn('foo bar baz');
        $dossierB->shouldReceive('getType')->andReturn($typeB = DossierType::WOO_DECISION);
        $dossierB->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());

        $this->repository->expects('getRecentDossiers')->with(5, null)->andReturn([
            $dossierA,
            $dossierB,
        ]);

        $result = $this->factory->getRecentDossiers(5);

        self::assertCount(2, $result);
        self::assertEquals($typeA, $result[0]->reference->getType());
        self::assertEquals($typeB, $result[1]->reference->getType());
    }

    public function testGetRecentDossiersForDepartment(): void
    {
        $dossierA = \Mockery::mock(AbstractDossier::class);
        $dossierA->shouldReceive('getDossierNr')->andReturn('foo-123');
        $dossierA->shouldReceive('getDocumentPrefix')->andReturn('BAR');
        $dossierA->shouldReceive('getTitle')->andReturn('foo bar baz');
        $dossierA->shouldReceive('getType')->andReturn($typeA = DossierType::COVENANT);
        $dossierA->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());

        $dossierB = \Mockery::mock(AbstractDossier::class);
        $dossierB->shouldReceive('getDossierNr')->andReturn('foo-123');
        $dossierB->shouldReceive('getDocumentPrefix')->andReturn('BAR');
        $dossierB->shouldReceive('getTitle')->andReturn('foo bar baz');
        $dossierB->shouldReceive('getType')->andReturn($typeB = DossierType::WOO_DECISION);
        $dossierB->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable());

        $department = \Mockery::mock(Department::class);

        $this->repository->expects('getRecentDossiers')->with(5, $department)->andReturn([
            $dossierA,
            $dossierB,
        ]);

        $result = $this->factory->getRecentDossiersForDepartment(5, $department);

        self::assertCount(2, $result);
        self::assertEquals($typeA, $result[0]->reference->getType());
        self::assertEquals($typeB, $result[1]->reference->getType());
    }
}
