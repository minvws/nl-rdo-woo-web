<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Form\Dossier;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Form\Dossier\DossierFormFactory;
use Shared\Service\Security\Roles;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DossierFormTest extends UnitTestCase
{
    public function testDossierNumberFieldAddedWhenSecurityGrantsAdmin(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNumber', TextType::class, Mockery::any());

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(true);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNumberField();
    }

    public function testDossierNumberFieldNotAddedWhenSecurityDeniesAndDossierIsPublished(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(false);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNumberField();
    }

    public function testDossierNumberFieldAddedForNewOrConceptDossier(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNumber', TextType::class, Mockery::any());

        $security = Mockery::mock(Security::class);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNumberField();
    }

    public function testDossierNumberFieldAddedForNonNewOrConceptDossier(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNumber', TextType::class, Mockery::any());

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(true);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNumberField();
    }

    public function testDossierNumberFieldAddedForNonNewOrConceptDossierAndNoOrganistionAdmin(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(false);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNumberField();
    }
}
