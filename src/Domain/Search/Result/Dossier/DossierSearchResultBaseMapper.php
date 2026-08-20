<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier;

use MinVWS\TypeArray\TypeArray;
use Shared\ApplicationId;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Result\HighlightMapperTrait;
use Shared\Domain\Search\Result\ResultEntryInterface;

use function array_map;
use function is_null;
use function sprintf;

readonly class DossierSearchResultBaseMapper
{
    use HighlightMapperTrait;

    /**
     * @param array<array-key, string> $highlightPaths
     */
    public function map(
        TypeArray $hit,
        ProvidesDossierTypeSearchResultInterface $repository,
        ElasticDocumentType $documentType,
        array $highlightPaths = [ElasticField::TITLE->value, ElasticField::SUMMARY->value],
        ApplicationId $applicationId = ApplicationId::PUBLIC,
    ): ?ResultEntryInterface {
        $documentPrefix = $hit->getStringOrNull('[fields][document_prefix][0]');
        $dossierNumber = $hit->getStringOrNull('[fields][dossier_number][0]');
        if (is_null($documentPrefix) || is_null($dossierNumber)) {
            return null;
        }

        $resultViewModel = $repository->getSearchResultViewModel($documentPrefix, $dossierNumber, $applicationId);
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

        return new DossierSearchResultEntry(
            $documentType,
            $resultViewModel,
            $highlightData,
        );
    }
}
