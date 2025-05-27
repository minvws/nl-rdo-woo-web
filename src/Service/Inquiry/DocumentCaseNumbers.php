<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Symfony\Component\Uid\Uuid;

class DocumentCaseNumbers
{
    /**
     * @param array<string> $caseNrs
     */
    public function __construct(
        public ?Uuid $documentId,
        public array $caseNrs,
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
            return new self(null, []);
        }

        $documentId = $data[0]['id'];
        $inquiryNrs = [];
        foreach ($data as $row) {
            if ($row['casenr'] === null) {
                continue;
            }

            $inquiryNrs[] = $row['casenr'];
        }

        return new self($documentId, $inquiryNrs);
    }

    public static function fromDocumentEntity(Document $document): self
    {
        return new self(
            $document->getId(),
            $document->getInquiries()->map(
                fn (Inquiry $inquiry) => $inquiry->getCasenr()
            )->toArray(),
        );
    }
}
