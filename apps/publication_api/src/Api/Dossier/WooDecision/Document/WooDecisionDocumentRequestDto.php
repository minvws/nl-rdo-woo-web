<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Validator\AllowedFileExtension;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\Constraints as Assert;

class WooDecisionDocumentRequestDto
{
    /**
     * @param array<array-key, string> $caseNumbers
     * @param array<array-key, string> $grounds
     * @param array<array-key, string> $links
     * @param array<array-key, string> $refersTo
     */
    public function __construct(
        public array $caseNumbers,
        public PlainDate $documentDate,
        public string $documentId,
        public ExternalId $externalId,
        public ?int $familyId,
        #[AllowedFileExtension(UploadGroupId::API_WOO_DECISION_DOCUMENTS)]
        public FileName $fileName,
        #[Assert\All([
            new Assert\Choice(choices: Citation::ALL_GROUND_KEYS),
        ])]
        public array $grounds,
        public bool $isSuspended,
        public Judgement $judgement,
        public array $links,
        public string $matter,
        public array $refersTo,
        public ?string $remark,
        public SourceType $sourceType,
        public ?int $threadId,
    ) {
    }
}
