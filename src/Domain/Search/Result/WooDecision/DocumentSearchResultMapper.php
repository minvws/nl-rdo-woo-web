<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\WooDecision;

use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SubTypeEntry;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use Jaytaph\TypeArray\TypeArray;

readonly class DocumentSearchResultMapper
{
    use HighlightMapperTrait;

    public function __construct(
        private DocumentRepository $documentRepository,
        private DossierRepository $dossierRepository,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        $documentNr = $hit->getStringOrNull('[fields][document_nr][0]');
        if (is_null($documentNr)) {
            return null;
        }

        $document = $this->documentRepository->getDocumentSearchEntry($documentNr);
        if (! $document) {
            return null;
        }

        $dossiers = $this->dossierRepository->getDossierReferencesForDocument($documentNr);

        $highlightPaths = [
            '[highlight][pages.content]',
            '[highlight][dossiers.title]',
            '[highlight][dossiers.summary]',
        ];
        $highlightData = $this->getHighlightData($hit, $highlightPaths);

        return new SubTypeEntry(
            $document,
            $dossiers,
            $highlightData,
        );
    }
}
