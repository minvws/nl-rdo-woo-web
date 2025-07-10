<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType\WooDecisionDocument;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\Schema\ElasticHighlights;
use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use App\Service\Security\ApplicationMode\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

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

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
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

        $highlightData = $this->getHighlightData($hit, ElasticHighlights::getPaths());

        return new SubTypeSearchResultEntry(
            $document,
            $dossiers,
            $highlightData,
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
        );
    }
}
