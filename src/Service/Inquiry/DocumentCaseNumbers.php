<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Symfony\Component\Uid\Uuid;

class DocumentCaseNumbers
{
    public function __construct(
        public ?Uuid $documentId,
        public CaseNumbers $caseNumbers,
    ) {
    }

    public function isDocumentNotFound(): bool
    {
        return $this->documentId === null;
    }

    /**
     * @param array<array-key, array{id:Uuid, casenr:string|null}> $data
     */
    public static function fromArray(array $data): self
    {
        if (count($data) === 0) {
            return new self(null, CaseNumbers::empty());
        }

        $documentId = $data[0]['id'];
        $inquiryNrs = [];
        foreach ($data as $row) {
            if ($row['casenr'] === null) {
                continue;
            }

            $inquiryNrs[] = $row['casenr'];
        }

        return new self($documentId, new CaseNumbers($inquiryNrs));
    }

    public static function fromDocumentEntity(Document $document): self
    {
        return new self(
            $document->getId(),
            CaseNumbers::forDocument($document),
        );
    }
}
