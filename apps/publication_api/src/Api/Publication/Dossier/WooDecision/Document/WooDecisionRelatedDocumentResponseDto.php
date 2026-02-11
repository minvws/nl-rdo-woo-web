<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

use function array_map;
use function array_values;

final readonly class WooDecisionRelatedDocumentResponseDto
{
    public function __construct(
        public ?string $documentId,
        public ?string $externalId,
    ) {
    }

    /**
     * @param array<array-key,Document> $entities
     *
     * @return list<self>
     */
    public static function fromEntities(array $entities): array
    {
        return array_values(array_map(self::fromEntity(...), $entities));
    }

    public static function fromEntity(Document $document): self
    {
        return new self(
            $document->getDocumentId(),
            (string) $document->getExternalId(),
        );
    }
}
