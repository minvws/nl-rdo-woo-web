<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;

final readonly class WooDecisionDocumentResponseDto
{
    /**
     * @param list<string> $caseNumbers
     * @param list<string> $grounds
     * @param list<string> $links
     * @param list<WooDecisionRelatedDocumentResponseDto> $refersTo
     */
    public function __construct(
        public array $caseNumbers,
        public ?PlainDate $documentDate,
        public ?string $documentId,
        public string $documentNr,
        public ?ExternalId $externalId,
        public ?int $familyId,
        public array $grounds,
        public bool $isSuspended,
        public bool $isUploaded,
        public bool $isWithdrawn,
        public ?Judgement $judgement,
        public array $links,
        public array $refersTo,
        public ?string $remark,
        public ?int $threadId,
        public UploadStatus $uploadStatus,
    ) {
    }
}
