<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Covenant;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\HighlightMapperTrait;
use App\Domain\Search\Result\MainTypeEntry;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Repository\CovenantRepository;
use Jaytaph\TypeArray\TypeArray;

readonly class CovenantResultMapper
{
    use HighlightMapperTrait;

    public function __construct(
        private CovenantRepository $repository,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        $prefix = $hit->getStringOrNull('[fields][document_prefix][0]');
        $dossierNr = $hit->getStringOrNull('[fields][dossier_nr][0]');
        if (is_null($prefix) || is_null($dossierNr)) {
            return null;
        }

        $dossierViewModel = $this->repository->getSearchEntry($prefix, $dossierNr);
        if ($dossierViewModel === null) {
            return null;
        }

        $highlightData = $this->getHighlightData(
            hit: $hit,
            paths: [
                '[highlight][title]',
                '[highlight][summary]',
            ]
        );

        return new MainTypeEntry(
            ElasticDocumentType::COVENANT,
            $dossierViewModel,
            $highlightData,
        );
    }
}
