<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\Validator\ConstraintViolation;

use function sprintf;

/**
 * Temporary safety guard until #7598 supports multi-dossier document writes.
 */
final readonly class WooDecisionDocumentWriteGuard
{
    private const string SHARED_DOCUMENT_MESSAGE = 'Document "%s" cannot be modified through the Publication API '
        . 'because it is linked to multiple dossiers.';
    private const string SECOND_DOSSIER_LINK_MESSAGE = 'Document "%s" cannot be linked to another dossier through '
        . 'the Publication API yet.';

    public function __construct(
        private DocumentRepository $documentRepository,
    ) {
    }

    /**
     * @param array<int, DocumentNumber> $documentNumbers
     */
    public function assertCanWrite(?WooDecision $dossier, array $documentNumbers): void
    {
        if ($documentNumbers === []) {
            return;
        }

        $documentsByNumber = $this->findDocumentsByNumber($documentNumbers);

        $violations = [];
        foreach ($documentNumbers as $index => $documentNumber) {
            $document = $documentsByNumber[$documentNumber->toString()] ?? null;

            if (! $document instanceof Document) {
                continue;
            }

            $dossiers = $document->getDossiers();
            $messageTemplate = match (true) {
                $dossiers->count() > 1 => self::SHARED_DOCUMENT_MESSAGE,
                $dossier instanceof WooDecision && $dossiers->contains($dossier) => null,
                default => self::SECOND_DOSSIER_LINK_MESSAGE,
            };

            if ($messageTemplate !== null) {
                $violations[] = $this->createViolation($document, $documentNumber, $index, $messageTemplate);
            }
        }

        if ($violations !== []) {
            throw new ValidationException(ConstraintViolationBuilder::createList(...$violations));
        }
    }

    /**
     * @param array<int, DocumentNumber> $documentNumbers
     *
     * @return array<string, Document>
     */
    private function findDocumentsByNumber(array $documentNumbers): array
    {
        $documentsByNumber = [];

        foreach ($this->documentRepository->findByDocumentNumbersCaseInsensitive($documentNumbers) as $document) {
            $documentsByNumber[$document->getDocumentNumber()->toString()] = $document;
        }

        return $documentsByNumber;
    }

    private function createViolation(
        Document $document,
        DocumentNumber $documentNumber,
        int $index,
        string $messageTemplate,
    ): ConstraintViolation {
        return new ConstraintViolation(
            sprintf($messageTemplate, $documentNumber->toString()),
            null,
            [],
            $document,
            sprintf('documents[%d]', $index),
            $documentNumber->toString(),
        );
    }
}
