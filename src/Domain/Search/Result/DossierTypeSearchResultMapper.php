<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use Jaytaph\TypeArray\TypeArray;

readonly class DossierTypeSearchResultMapper
{
    use HighlightMapperTrait;

    /**
     * @param string[] $highlightPaths
     */
    public function map(
        TypeArray $hit,
        ProvidesDossierTypeSearchResultInterface $repository,
        ElasticDocumentType $documentType,
        array $highlightPaths = ['title', 'summary'],
    ): ?ResultEntryInterface {
        $prefix = $hit->getStringOrNull('[fields][document_prefix][0]');
        $dossierNr = $hit->getStringOrNull('[fields][dossier_nr][0]');
        if (is_null($prefix) || is_null($dossierNr)) {
            return null;
        }

        $resultViewModel = $repository->getSearchResultViewModel($prefix, $dossierNr);
        if ($resultViewModel === null) {
            return null;
        }

        $highlightPaths = array_map(
            static fn (string $path) => sprintf('[highlight][%s]', $path),
            $highlightPaths,
        );

        $highlightData = $this->getHighlightData(
            hit: $hit,
            paths: $highlightPaths,
        );

        return new MainTypeEntry(
            $documentType,
            $resultViewModel,
            $highlightData,
        );
    }
}
