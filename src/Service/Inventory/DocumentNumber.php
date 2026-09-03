<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use RuntimeException;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;
use Shared\ValueObject\PublicationContext;
use Stringable;

use function count;
use function preg_match;
use function str_starts_with;
use function strlen;
use function substr;

readonly class DocumentNumber implements Stringable
{
    private string $value;

    private function __construct(
        public string $prefix,
        public ?DocumentMatter $matter,
        public DocumentId $id,
    ) {
        $value = $prefix;
        if ($this->matter !== null) {
            $value .= '-' . $this->matter->toString();
        }
        $value .= '-' . $id->toString();

        if (strlen($value) > 255) {
            throw new RuntimeException('Document number maximum length exceeded');
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getMatter(): ?DocumentMatter
    {
        return $this->matter;
    }

    public static function fromPrefixMatterAndInput(string $prefix, ?DocumentMatter $defaultMatter, string $input): self
    {
        // If the prefix is included remove it
        if (str_starts_with($input, $prefix)) {
            $input = substr($input, strlen($prefix) + 1);
        }

        preg_match('/(.*)([-_])(.*)$/', $input, $matches);
        if (count($matches) === 4) {
            $matter = DocumentMatter::create($matches[1]);
            $documentId = DocumentId::create($matches[3]);
        } else {
            // If there is just one part input is a documentId without matter, the default matter is used
            $matter = $defaultMatter;
            $documentId = DocumentId::create($input);
        }

        return new self($prefix, $matter, $documentId);
    }

    public static function fromReferral(WooDecision $dossier, Document $referringDocument, string $referral): self
    {
        // Create an instance for the referring document first, to use it's matter as the fallback matter.
        $referringDocNr = self::fromDossierAndDocument($dossier, $referringDocument);

        return self::fromPrefixMatterAndInput($dossier->getDocumentPrefix(), $referringDocNr->getMatter(), $referral);
    }

    public static function fromDossierAndDocument(WooDecision $dossier, Document $document): self
    {
        // Cut prefix and it's separator from the documentNumber start, leaving matter and documentId
        $matterAndDocId = substr($document->getDocumentNumber(), strlen($dossier->getDocumentPrefix()) + 1);

        // Cut documentId and it's separator from the documentNumber end, leaving just matter
        $matterStr = substr($matterAndDocId, 0, -(strlen($document->getDocumentId()->toString()) + 1));
        $matter = DocumentMatter::create($matterStr ?: '0');

        return new self($dossier->getDocumentPrefix(), $matter, $document->getDocumentId());
    }

    public static function fromPublicationContextAndDossierId(PublicationContext $publicationContext, DocumentId $documentId): self
    {
        return new self($publicationContext->toString(), null, $documentId);
    }

    public function toString(): string
    {
        return $this->__toString();
    }
}
