<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Document;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;

use function array_map;
use function array_values;

final readonly class WooDecisionDocumentResponseDto
{
    /**
     * @param string[] $caseNumbers
     * @param string[] $grounds
     * @param string[] $links
     * @param list<WooDecisionRelatedDocumentResponseDto> $refersTo
     */
    public function __construct(
        public array $caseNumbers,
        public ?DateTimeImmutable $date,
        public ?string $documentId,
        public string $documentNr,
        public ?string $externalId,
        public ?int $familyId,
        public array $grounds,
        public bool $isSuspended,
        public bool $isUploaded,
        public bool $isWithdrawn,
        public ?Judgement $judgement,
        public array $links,
        public ?string $period,
        public array $refersTo,
        public ?string $remark,
        public ?int $threadId,
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
            array_map(fn (Inquiry $inquiry) => $inquiry->getCasenr(), $document->getInquiries()->toArray()),
            $document->getDocumentDate(),
            $document->getDocumentId(),
            $document->getDocumentNr(),
            $document->getExternalId()?->__toString(),
            $document->getFamilyId(),
            $document->getGrounds(),
            $document->isSuspended(),
            $document->isUploaded(),
            $document->isWithdrawn(),
            $document->getJudgement(),
            $document->getLinks(),
            $document->getPeriod(),
            WooDecisionRelatedDocumentResponseDto::fromEntities($document->getRefersTo()->toArray()),
            $document->getRemark(),
            $document->getThreadId(),
        );
    }
}
