<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

readonly class DocumentNumber implements \Stringable
{
    private string $value;

    private function __construct(
        public string $prefix,
        public string $matter,
        public string $id,
    ) {
        $value = $prefix . '-' . $matter . '-' . $id;
        if (strlen($value) > 255) {
            throw new \RuntimeException('Document number maximum length exceeded');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getMatter(): string
    {
        return $this->matter;
    }

    public static function fromDossierAndDocumentMetadata(WooDecision $dossier, DocumentMetadata $metadata): self
    {
        return new self($dossier->getDocumentPrefix(), $metadata->getMatter(), $metadata->getId());
    }

    public static function fromString(string $prefix, string $defaultMatter, string $input): self
    {
        // If the prefix is included remove it
        if (str_starts_with($input, $prefix)) {
            $input = substr($input, strlen($prefix) + 1);
        }

        preg_match('/(.*)([-_])([a-z0-9.]+)$/', $input, $matches);
        if (count($matches) === 4) {
            $matter = $matches[1];
            $documentId = $matches[3];
        } else {
            // If there is just one part input is a documentId without matter, the default matter is used
            $matter = $defaultMatter;
            $documentId = $input;
        }

        return new self($prefix, $matter, $documentId);
    }

    public static function fromReferral(WooDecision $dossier, Document $referringDocument, string $referral): self
    {
        // Create an instance for the referring document first, to use it's matter as the fallback matter.
        $referringDocNr = self::fromDossierAndDocument($dossier, $referringDocument);

        return self::fromString($dossier->getDocumentPrefix(), $referringDocNr->getMatter(), $referral);
    }

    public static function fromDossierAndDocument(WooDecision $dossier, Document $document): self
    {
        if ($document->getDocumentId() === null) {
            throw new \RuntimeException('Document has no documentId');
        }

        // Cut prefix and it's separator from the documentNr start, leaving matter and documentId
        $matterAndDocId = substr($document->getDocumentNr(), strlen($dossier->getDocumentPrefix()) + 1);

        // Cut documentId and it's separator from the documentNr end, leaving just matter
        $matter = substr($matterAndDocId, 0, -(strlen($document->getDocumentId()) + 1));

        return new self($dossier->getDocumentPrefix(), $matter, $document->getDocumentId());
    }
}
