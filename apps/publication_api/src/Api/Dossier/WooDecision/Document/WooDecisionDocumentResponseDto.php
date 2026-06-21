<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Document;

use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Serializer\Attribute\SerializedName;

final readonly class WooDecisionDocumentResponseDto
{
    /**
     * @param list<string> $inquiryNumbers
     * @param list<string> $grounds
     * @param list<string> $links
     * @param list<WooDecisionRelatedDocumentResponseDto> $refersTo
     */
    public function __construct(
        public array $inquiryNumbers,
        public ?PlainDate $documentDate,
        public ?DocumentId $documentId,
        public string $documentNr,
        public ?ExternalId $externalId,
        public ?int $familyId,
        public ?string $filename,
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
        #[SerializedName('_links')]
        public LinkCollection $halLinks,
    ) {
    }
}
