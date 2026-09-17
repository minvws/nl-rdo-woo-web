<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;
use Shared\ValueObject\DocumentNumber;

use function count;
use function preg_match;
use function str_starts_with;
use function strlen;
use function substr;

final readonly class LegacyDocumentNumberFactory
{
    public function fromPrefixMatterAndInput(
        string $documentPrefix,
        ?DocumentMatter $defaultMatter,
        string $input,
    ): DocumentNumber {
        if (str_starts_with($input, $documentPrefix)) {
            $input = substr($input, strlen($documentPrefix) + 1);
        }

        preg_match('/(.*)([-_])(.*)$/', $input, $matches);
        if (count($matches) === 4) {
            $documentMatter = DocumentMatter::create($matches[1]);
            $documentId = DocumentId::create($matches[3]);
        } else {
            $documentMatter = $defaultMatter;
            $documentId = DocumentId::create($input);
        }

        return $this->createDocumentNumber($documentPrefix, $documentMatter, $documentId);
    }

    public function fromReferral(
        WooDecision $dossier,
        Document $referringDocument,
        string $referral,
    ): DocumentNumber {
        $documentPrefix = $dossier->getDocumentPrefix();

        return $this->fromPrefixMatterAndInput(
            $documentPrefix,
            $this->getMatterFromDossierAndDocument($documentPrefix, $referringDocument),
            $referral,
        );
    }

    public function fromDossierAndDocument(
        WooDecision $dossier,
        Document $document,
    ): DocumentNumber {
        $documentPrefix = $dossier->getDocumentPrefix();
        $documentMatter = $this->getMatterFromDossierAndDocument($documentPrefix, $document);

        return $this->createDocumentNumber(
            $documentPrefix,
            $documentMatter,
            $document->getDocumentId(),
        );
    }

    private function getMatterFromDossierAndDocument(
        string $documentPrefix,
        Document $document,
    ): DocumentMatter {
        $matterAndDocumentId = substr(
            $document->getDocumentNumber()->toString(),
            strlen($documentPrefix) + 1,
        );

        $matterString = substr(
            $matterAndDocumentId,
            0,
            -(strlen($document->getDocumentId()->toString()) + 1),
        );

        return DocumentMatter::create($matterString ?: '0');
    }

    private function createDocumentNumber(
        string $documentPrefix,
        ?DocumentMatter $documentMatter,
        DocumentId $documentId,
    ): DocumentNumber {
        $value = $documentPrefix;
        if ($documentMatter !== null) {
            $value .= '-' . $documentMatter->toString();
        }

        return DocumentNumber::fromString($value . '-' . $documentId->toString());
    }
}
