<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Department\Department;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\ViewModel\DossierFormParamBuilder;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Uid\Uuid;

final class DossierFormParamBuilderTest extends UnitTestCase
{
    private DossierFormParamBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DossierFormParamBuilder();

        parent::setUp();
    }

    public function testGetDepartmentsFieldParams(): void
    {
        $fooDepartment = \Mockery::mock(Department::class);
        $fooDepartment->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1ef401f7-958a-65c4-a92c-25f027c8b5e7'));
        $fooDepartment->shouldReceive('getName')->andReturn('foo');

        $barDepartment = \Mockery::mock(Department::class);
        $barDepartment->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'));
        $barDepartment->shouldReceive('getName')->andReturn('bar');

        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->expects('getOrganisation->getDepartments')->andReturn(
            new ArrayCollection([$fooDepartment, $barDepartment])
        );

        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('get->getData')->andReturn(new ArrayCollection([
            $fooDepartment,
        ]));
        $form->shouldReceive('get->getErrors')->andReturn(
            new FormErrorIterator(
                $form,
                [
                    new FormError('oops'),
                ]
            )
        );

        $this->assertMatchesJsonSnapshot(
            $this->builder->getDepartmentsFieldParams($dossier, $form),
        );
    }

    public function testGetDepartmentsFieldParamsWithEmptyFormData(): void
    {
        $fooDepartment = \Mockery::mock(Department::class);
        $fooDepartment->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1ef401f7-958a-65c4-a92c-25f027c8b5e7'));
        $fooDepartment->shouldReceive('getName')->andReturn('foo');

        $barDepartment = \Mockery::mock(Department::class);
        $barDepartment->shouldReceive('getId')->andReturn(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'));
        $barDepartment->shouldReceive('getName')->andReturn('bar');

        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->expects('getOrganisation->getDepartments')->andReturn(
            new ArrayCollection([$fooDepartment, $barDepartment])
        );

        $form = \Mockery::mock(FormInterface::class);
        $form->shouldReceive('get->getData')->andReturnNull();
        $form->shouldReceive('get->getErrors')->andReturn(
            new FormErrorIterator(
                $form,
                [
                    new FormError('oops'),
                ]
            )
        );

        $this->assertMatchesJsonSnapshot(
            $this->builder->getDepartmentsFieldParams($dossier, $form),
        );
    }
}
