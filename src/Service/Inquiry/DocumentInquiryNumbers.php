<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Symfony\Component\Uid\Uuid;

use function count;

class DocumentInquiryNumbers
{
    public function __construct(
        public ?Uuid $documentId,
        public InquiryNumbers $inquiryNumbers,
    ) {
    }

    public function isDocumentNotFound(): bool
    {
        return $this->documentId === null;
    }

    /**
     * @param array<array-key, array{id:Uuid, inquiryNumber:string|null}> $data
     */
    public static function fromArray(array $data): self
    {
        if (count($data) === 0) {
            return new self(null, InquiryNumbers::empty());
        }

        $documentId = $data[0]['id'];
        $inquiryNumbers = [];
        foreach ($data as $row) {
            if ($row['inquiryNumber'] === null) {
                continue;
            }

            $inquiryNumbers[] = $row['inquiryNumber'];
        }

        return new self($documentId, new InquiryNumbers($inquiryNumbers));
    }

    public static function fromDocumentEntity(Document $document): self
    {
        return new self(
            $document->getId(),
            InquiryNumbers::forDocument($document),
        );
    }
}
