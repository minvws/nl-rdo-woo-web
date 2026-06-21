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
use Symfony\Component\Uid\Uuid;

class DossierFormTest extends UnitTestCase
{
    public function testDossierNrFieldAddedWhenSecurityGrantsAdmin(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getDocumentPrefix')->andReturn('pfx');
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNr', TextType::class, Mockery::any());

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(true);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNrField();
    }

    public function testDossierNrFieldNotAddedWhenSecurityDeniesAndDossierIsPublished(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(false);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNrField();
    }

    public function testDossierNrFieldAddedForNewOrConceptDossier(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);
        $dossier->expects('getDocumentPrefix')->andReturn('pfx');
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNr', TextType::class, Mockery::any());

        $security = Mockery::mock(Security::class);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNrField();
    }

    public function testDossierNrFieldAddedForNonNewOrConceptDossier(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->expects('getDocumentPrefix')->andReturn('pfx');
        $dossier->expects('getId')->andReturn(Uuid::v6());

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);
        $builder->expects('add')->with('dossierNr', TextType::class, Mockery::any());

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(true);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNrField();
    }

    public function testDossierNrFieldAddedForNonNewOrConceptDossierAndNoOrganistionAdmin(): void
    {
        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getStatus')->andReturn(DossierStatus::PUBLISHED);

        $builder = Mockery::mock(FormBuilderInterface::class);
        $builder->expects('getData')->andReturn($dossier);

        $security = Mockery::mock(Security::class);
        $security->expects('isGranted')->with(Roles::ROLE_ORGANISATION_ADMIN)->andReturn(false);

        $dossierFormFactory = new DossierFormFactory($security);
        $dossierForm = $dossierFormFactory->for($builder);
        $dossierForm->addDossierNrField();
    }
}
