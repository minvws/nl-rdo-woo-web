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
use function sprintf;

readonly class DossierDocumentValidator
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
        $violations = ConstraintViolationBuilder::createList();

        foreach ($documents as $index => $document) {
            $documentViolations = $this->validator->validate(
                $document,
                groups: $this->getValidationGroups($document, $dossierStatus),
            );

            if ($documentViolations->count() === 0) {
                continue;
            }

            $violations->addAll(
                ConstraintViolationBuilder::prefixPropertyPaths($documentViolations, sprintf('documents.[%d].', $index)),
            );
        }

        if ($violations->count() > 0) {
            throw new ValidationException($violations, previous: new ValidationFailedException($documents, $violations));
        }
    }

    /**
     * @return array<array-key, string>
     */
    private function getValidationGroups(Document $document, DossierStatus $dossierStatus): array
    {
        $dossierStatuses = [$dossierStatus];
        foreach ($document->getDossiers() as $dossier) {
            $dossierStatuses[] = $dossier->getStatus();
        }

        $validationGroups = EnumHelper::getStringValues(
            DossierValidationGroup::getForLinkedDossierStatuses($dossierStatuses),
        );
        $validationGroups[] = Constraint::DEFAULT_GROUP;

        return $validationGroups;
    }
}
