<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\EnumHelper;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

use function array_diff;
use function in_array;

final readonly class DossierDocumentValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     */
    public function assertDocumentSetUnchangedInNonConcept(WooDecision $wooDecision, array $wooDecisionDocumentRequestDtos): void
    {
        if (! in_array($wooDecision->getStatus(), DossierStatus::nonConceptCases(), true)) {
            return;
        }

        $existingExternalIds = [];
        foreach ($wooDecision->getDocuments() as $document) {
            $externalId = $document->getExternalId();
            Assert::isInstanceOf($externalId, ExternalId::class);

            $existingExternalIds[] = $externalId->toString();
        }

        $incomingExternalIds = [];
        foreach ($wooDecisionDocumentRequestDtos as $wooDecisionDocumentRequestDto) {
            $incomingExternalIds[] = $wooDecisionDocumentRequestDto->externalId->toString();
        }

        if (array_diff($existingExternalIds, $incomingExternalIds) !== []) {
            throw new ValidationException(
                ConstraintViolationBuilder::createList(ConstraintViolationBuilder::forModifiedSubEntity('documents')),
            );
        }
    }

    /**
     * @param list<Document> $documents
     */
    public function validate(array $documents, DossierStatus $dossierStatus): void
    {
        $validationGroups = EnumHelper::getStringValues(
            DossierValidationGroup::getForStatus($dossierStatus),
        );
        $validationGroups[] = Constraint::DEFAULT_GROUP;

        try {
            $this->validateDocuments($documents, $validationGroups);
        } catch (ValidationFailedException $validationFailedException) {
            throw new ValidationException(
                ConstraintViolationBuilder::prefixPropertyPaths($validationFailedException->getViolations(), 'documents.'),
                previous: $validationFailedException,
            );
        }
    }

    /**
     * @param list<Document> $documents
     * @param array<array-key, string>|null $validationGroups
     */
    private function validateDocuments(array $documents, ?array $validationGroups = null): void
    {
        $errors = $this->validator->validate($documents, groups: $validationGroups);

        if ($errors->count() > 0) {
            throw new ValidationFailedException($documents, $errors);
        }
    }
}
