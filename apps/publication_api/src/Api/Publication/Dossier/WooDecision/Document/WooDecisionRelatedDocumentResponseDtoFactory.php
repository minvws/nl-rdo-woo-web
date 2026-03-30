<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;

use function array_map;
use function array_values;

readonly class WooDecisionRelatedDocumentResponseDtoFactory
{
    /**
     * @param array<array-key,Document> $entities
     *
     * @return list<WooDecisionRelatedDocumentResponseDto>
     */
    public function fromEntities(array $entities): array
    {
        return array_values(array_map($this->fromEntity(...), $entities));
    }

    public function fromEntity(Document $document): WooDecisionRelatedDocumentResponseDto
    {
        return new WooDecisionRelatedDocumentResponseDto(
            $document->getDocumentId(),
            $document->getExternalId(),
        );
    }
}
