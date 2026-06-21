<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use ApiPlatform\Validator\Exception\ValidationException as ApiPlatformValidationException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\ValueObject\ExternalId;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function in_array;
use function sprintf;
use function strval;

final readonly class WooDecisionDocumentValidator
{
    public function __construct(
        private DocumentRepository $documentRepository,
    ) {
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $wooDecisionDocumentRequestDtos
     */
    public function validate(array $wooDecisionDocumentRequestDtos): void
    {
        $references = $this->collectRefersToReferences($wooDecisionDocumentRequestDtos);

        if ($references === []) {
            return;
        }

        $selfReferences = $this->collectSelfReferences($wooDecisionDocumentRequestDtos, $references);

        $requestDocumentExternalIds = $this->getRequestDocumentExternalIds($wooDecisionDocumentRequestDtos);

        $dbExternalIds = $this->getExternalIdsFromReferencesNotInRequest($references, $requestDocumentExternalIds);

        $violations = [];

        if ($selfReferences !== []) {
            $violations = $this->buildSelfReferenceViolations($selfReferences, $wooDecisionDocumentRequestDtos);
        }

        if ($dbExternalIds !== []) {
            $existing = $this->documentRepository->findExistingExternalIds($dbExternalIds);
            $existingLookup = array_flip($existing);
            $nonExisting = array_values(
                array_filter(
                    $dbExternalIds,
                    static fn (ExternalId $externalId): bool => ! array_key_exists($externalId->__toString(), $existingLookup),
                ),
            );

            if ($nonExisting !== []) {
                $violations = array_merge(
                    $violations,
                    $this->buildRefersToViolations($references, $nonExisting, $wooDecisionDocumentRequestDtos),
                );
            }
        }

        if ($violations !== []) {
            throw new ApiPlatformValidationException(new ConstraintViolationList($violations));
        }
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $dtos
     *
     * @return list<array{externalId: ExternalId, docIndex: int, refersToIndex: int}>
     */
    private function collectRefersToReferences(array $dtos): array
    {
        $references = [];
        foreach ($dtos as $docIndex => $dto) {
            foreach ($dto->refersTo as $refersToIndex => $externalId) {
                $references[] = [
                    'externalId' => $externalId,
                    'docIndex' => $docIndex,
                    'refersToIndex' => $refersToIndex,
                ];
            }
        }

        return $references;
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $dtos
     * @param list<array{externalId: ExternalId, docIndex: int, refersToIndex: int}> $references
     *
     * @return list<array{externalId: ExternalId, docIndex: int, refersToIndex: int}>
     */
    private function collectSelfReferences(array $dtos, array $references): array
    {
        $selfReferences = [];
        foreach ($references as $reference) {
            $dto = $dtos[$reference['docIndex']];
            if ($dto->externalId->__toString() === $reference['externalId']->__toString()) {
                $selfReferences[] = $reference;
            }
        }

        return $selfReferences;
    }

    /**
     * @param list<array{externalId: ExternalId, docIndex: int, refersToIndex: int}> $selfReferences
     * @param list<WooDecisionDocumentRequestDto> $dtos
     *
     * @return list<ConstraintViolation>
     */
    private function buildSelfReferenceViolations(array $selfReferences, array $dtos): array
    {
        $violations = [];
        foreach ($selfReferences as ['externalId' => $externalId, 'docIndex' => $docIndex, 'refersToIndex' => $refersToIndex]) {
            $violations[] = new ConstraintViolation(
                'A document cannot refer to itself',
                null,
                [],
                $dtos[$docIndex],
                sprintf('documents[%d].refersTo[%d]', $docIndex, $refersToIndex),
                $externalId,
            );
        }

        return $violations;
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $dtos
     *
     * @return list<ExternalId>
     */
    private function getRequestDocumentExternalIds(array $dtos): array
    {
        return array_map(
            static fn (WooDecisionDocumentRequestDto $dto): ExternalId => $dto->externalId,
            $dtos,
        );
    }

    /**
     * @param list<array{externalId: ExternalId, docIndex: int, refersToIndex: int}> $references
     * @param list<ExternalId> $requestDocumentExternalIds
     *
     * @return list<ExternalId>
     */
    private function getExternalIdsFromReferencesNotInRequest(array $references, array $requestDocumentExternalIds): array
    {
        $requestIds = array_map(
            static fn (ExternalId $externalId): string => $externalId->__toString(),
            $requestDocumentExternalIds,
        );

        $externalIds = [];

        foreach ($references as $reference) {
            $externalId = $reference['externalId'];

            if (! in_array($externalId->__toString(), $requestIds, strict: true)) {
                $externalIds[] = $externalId;
            }
        }

        return $externalIds;
    }

    /**
     * @param list<array{externalId: ExternalId, docIndex: int, refersToIndex: int}> $references
     * @param list<ExternalId> $nonExisting
     * @param list<WooDecisionDocumentRequestDto> $dtos
     *
     * @return list<ConstraintViolation>
     */
    private function buildRefersToViolations(array $references, array $nonExisting, array $dtos): array
    {
        $nonExistingKeys = array_flip(array_map(strval(...), $nonExisting));

        $violations = [];
        foreach ($references as ['externalId' => $externalId, 'docIndex' => $docIndex, 'refersToIndex' => $refersToIndex]) {
            if (! array_key_exists($externalId->__toString(), $nonExistingKeys)) {
                continue;
            }

            $violations[] = new ConstraintViolation(
                'The referenced document could not be found',
                null,
                [],
                $dtos[$docIndex],
                sprintf('documents[%d].refersTo[%d]', $docIndex, $refersToIndex),
                $externalId,
            );
        }

        return $violations;
    }
}
