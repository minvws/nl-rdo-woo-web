<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Service\DossierService;
use Shared\Service\DossierWizard\WizardStatusFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DossierServiceTest extends UnitTestCase
{
    public function testValidateCompletionForPublishedWooDecision(): void
    {
        $doctrine = \Mockery::mock(EntityManagerInterface::class);
        $statusFactory = \Mockery::mock(WizardStatusFactory::class);

        $dossierService = new DossierService(
            $doctrine,
            $statusFactory,
            \Mockery::mock(SearchDispatcher::class),
            \Mockery::mock(ValidatorInterface::class),
        );

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->shouldReceive('hasWithdrawnOrSuspendedDocuments')->andReturnTrue();
        $dossier->expects('setCompleted')->with(false);

        $doctrine->expects('persist')->with($dossier);
        $doctrine->expects('flush');

        $statusFactory->expects('getWizardStatus->isCompleted')->andReturnFalse();

        self::assertFalse($dossierService->validateCompletion($dossier));
    }

    public function testValidate(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $constraintViolationList = \Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(0);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($dossier, null, \array_column(DossierValidationGroup::cases(), 'value'))
            ->andReturn($constraintViolationList);

        $dossierService = new DossierService(
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(WizardStatusFactory::class),
            \Mockery::mock(SearchDispatcher::class),
            $validator,
        );

        $dossierService->validate($dossier);
    }

    public function testValidateWithErrors(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $constraintViolationList = \Mockery::mock(ConstraintViolationListInterface::class);
        $constraintViolationList->expects('count')
            ->andReturn(1);

        $validator = \Mockery::mock(ValidatorInterface::class);
        $validator->expects('validate')
            ->with($dossier, null, \array_column(DossierValidationGroup::cases(), 'value'))
            ->andReturn($constraintViolationList);

        $dossierService = new DossierService(
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(WizardStatusFactory::class),
            \Mockery::mock(SearchDispatcher::class),
            $validator,
        );

        $this->expectException(ValidationFailedException::class);
        $dossierService->validate($dossier);
    }
}
