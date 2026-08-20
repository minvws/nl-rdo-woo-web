<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class DossierValidatorTest extends UnitTestCase
{
    public function testValidateDossierSucceeds(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);

        $dossierService = Mockery::mock(DossierService::class);
        $dossierService->expects('validate')
            ->with($dossier, [
                DossierValidationGroup::DETAILS,
                DossierValidationGroup::DECISION,
                DossierValidationGroup::DOCUMENTS,
                DossierValidationGroup::PUBLICATION,
                DossierValidationGroup::CONTENT,
                DossierValidationGroup::PUBLICATION_LOCKED,
            ]);

        $dossierValidator = new DossierValidator($dossierService);

        $dossierValidator->validateDossier($dossier);
    }

    public function testValidateDossierRefreshesAndRethrowsOnValidationFailure(): void
    {
        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getStatus')
            ->andReturn(DossierStatus::CONCEPT);

        $dossierService = Mockery::mock(DossierService::class);
        $dossierService->expects('validate')
            ->andThrow(new ValidationFailedException($dossier, new ConstraintViolationList()));
        $dossierService->expects('refreshDossier')
            ->with($dossier);

        $dossierValidator = new DossierValidator($dossierService);

        $this->expectException(ValidationException::class);
        $dossierValidator->validateDossier($dossier);
    }
}
