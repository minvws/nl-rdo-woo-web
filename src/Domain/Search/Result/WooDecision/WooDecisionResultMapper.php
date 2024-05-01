<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\WooDecision;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\MainTypeEntry;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Repository\DossierRepository;
use Jaytaph\TypeArray\TypeArray;

readonly class WooDecisionResultMapper
{
    use HighlightMapperTrait;

    public function __construct(
        private DossierRepository $repository,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        $prefix = $hit->getStringOrNull('[fields][document_prefix][0]');
        $dossierNr = $hit->getStringOrNull('[fields][dossier_nr][0]');
        if (is_null($prefix) || is_null($dossierNr)) {
            return null;
        }

        $dossier = $this->repository->getDossierSearchEntry($prefix, $dossierNr);
        if (! $dossier) {
            return null;
        }

        $highlightData = $this->getHighlightData(
            hit: $hit,
            paths: [
                '[highlight][title]',
                '[highlight][summary]',
                '[highlight][decision_content]',
            ]
        );

        return new MainTypeEntry(
            ElasticDocumentType::WOO_DECISION,
            $dossier,
            $highlightData,
        );
    }
}
