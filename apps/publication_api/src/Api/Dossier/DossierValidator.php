<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Service\DossierService;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class DossierValidator
{
    public function __construct(
        private DossierService $dossierService,
    ) {
    }

    public function validateDossier(AbstractDossier $dossier): void
    {
        $validationGroups = DossierValidationGroup::getForStatus($dossier->getStatus());

        try {
            $this->dossierService->validate($dossier, $validationGroups);
        } catch (ValidationFailedException $validationFailedException) {
            $this->dossierService->refreshDossier($dossier);
            throw new ValidationException($validationFailedException->getViolations(), previous: $validationFailedException);
        }
    }
}
