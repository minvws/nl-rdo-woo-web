<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form\Dossier\RequestForAdvice;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Uid\Uuid;

class DetailsTypeTest extends UnitTestCase
{
    use DossierFormBuilderTrait;

    public function testDossierNrFieldAddedWhenAllowEditIsTrue(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getDocumentPrefix')->andReturn('pfx');
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNr', TextType::class, Mockery::any());

        $this->addDossierNrField($builder, true);
    }

    public function testDossierNrFieldNotAddedWhenAllowEditIsFalseAndDossierIsPublished(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);

        $this->addDossierNrField($builder, false);
    }

    public function testDossierNrFieldAddedForConceptDossierRegardlessOfAllowEdit(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossier->expects('getDocumentPrefix')->andReturn('pfx');
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNr', TextType::class, Mockery::any());

        $this->addDossierNrField($builder, false);
    }
}
