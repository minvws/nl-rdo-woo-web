<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\SubType\WooDecisionDocument;

use MinVWS\TypeArray\TypeArray;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticHighlights;
use Shared\Domain\Search\Result\HighlightMapperTrait;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Domain\Search\Result\SubType\SubTypeSearchResultEntry;

use function is_null;

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

    public function map(TypeArray $hit, ApplicationId $applicationId = ApplicationId::PUBLIC): ?ResultEntryInterface
    {
        $documentNumber = $hit->getStringOrNull('[fields][document_number][0]');
        if (is_null($documentNumber)) {
            return null;
        }

        $document = $this->documentRepository->getDocumentSearchEntry($documentNumber);
        if (! $document) {
            return null;
        }

        $dossiers = $this->wooDecisionRepository->getDossierReferencesForDocument($documentNumber);

        $highlightData = $this->getHighlightData($hit, ElasticHighlights::getPaths());

        return new SubTypeSearchResultEntry(
            $document,
            $dossiers,
            $highlightData,
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
        );
    }
}
