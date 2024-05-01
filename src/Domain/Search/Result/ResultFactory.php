<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Covenant\CovenantResultMapper;
use App\Domain\Search\Result\WooDecision\DocumentResultMapper;
use App\Domain\Search\Result\WooDecision\WooDecisionResultMapper;
use Jaytaph\TypeArray\TypeArray;

readonly class ResultFactory
{
    public function __construct(
        private WooDecisionResultMapper $wooDecisionMapper,
        private DocumentResultMapper $documentMapper,
        private CovenantResultMapper $covenantMapper,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        $type = ElasticDocumentType::from($hit->getString('[fields][type][0]'));

        return match ($type) {
            ElasticDocumentType::WOO_DECISION => $this->wooDecisionMapper->map($hit),
            ElasticDocumentType::WOO_DECISION_DOCUMENT => $this->documentMapper->map($hit),
            ElasticDocumentType::COVENANT => $this->covenantMapper->map($hit),
        };
    }
}
