<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Document;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;

class WooDecisionDocumentRequestDto
{
    /**
     * @param string[] $caseNumbers
     * @param string[] $grounds
     * @param string[] $links
     * @param string[] $refersTo
     */
    public function __construct(
        public array $caseNumbers,
        public DateTimeImmutable $date,
        public string $documentId,
        public string $externalId,
        public ?int $familyId,
        public string $fileName,
        public array $grounds,
        public bool $isSuspended,
        public Judgement $judgement,
        public array $links,
        public string $matter,
        public ?string $period,
        public array $refersTo,
        public ?string $remark,
        public SourceType $sourceType,
        public ?int $threadId,
    ) {
    }
}
