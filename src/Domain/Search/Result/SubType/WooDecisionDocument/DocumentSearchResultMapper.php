<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType\WooDecisionDocument;

use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\WooDecisionRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use Jaytaph\TypeArray\TypeArray;

readonly class DocumentSearchResultMapper implements SearchResultMapperInterface
{
    use HighlightMapperTrait;

    public function __construct(
        private DocumentRepository $documentRepository,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::WOO_DECISION_DOCUMENT;
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

        $dossiers = $this->wooDecisionRepository->getDossierReferencesForDocument($documentNr);

        $highlightPaths = [
            '[highlight][pages.content]',
            '[highlight][dossiers.title]',
            '[highlight][dossiers.summary]',
        ];
        $highlightData = $this->getHighlightData($hit, $highlightPaths);

        return new SubTypeSearchResultEntry(
            $document,
            $dossiers,
            $highlightData,
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
        );
    }
}
