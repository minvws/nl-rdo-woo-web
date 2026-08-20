<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Service\EnumHelper;
use Shared\Service\MainDocumentService;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class DossierMainDocumentValidator
{
    public function __construct(
        private MainDocumentService $mainDocumentService,
    ) {
    }

    public function validate(AbstractMainDocument $mainDocument): void
    {
        $dossierStatus = $mainDocument->getDossier()->getStatus();
        $dossierValidationGroups = DossierValidationGroup::getForStatus($dossierStatus);
        $validationGroups = EnumHelper::getStringValues($dossierValidationGroups);
        $validationGroups[] = Constraint::DEFAULT_GROUP;

        try {
            $this->mainDocumentService->validate($mainDocument, $validationGroups);
        } catch (ValidationFailedException $validationFailedException) {
            $this->mainDocumentService->refreshMainDocument($mainDocument);

            throw new ValidationException(
                ConstraintViolationBuilder::prefixPropertyPaths($validationFailedException->getViolations(), 'mainDocument.'),
                previous: $validationFailedException,
            );
        }
    }
}
