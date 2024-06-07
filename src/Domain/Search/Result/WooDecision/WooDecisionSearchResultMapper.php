<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\WooDecision;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\DossierTypeSearchResultMapper;
use App\Domain\Search\Result\DossierTypeSearchResultMapperInterface;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Repository\DossierRepository;
use Jaytaph\TypeArray\TypeArray;

readonly class WooDecisionSearchResultMapper implements DossierTypeSearchResultMapperInterface
{
    public function __construct(
        private DossierTypeSearchResultMapper $baseMapper,
        private DossierRepository $repository,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::WOO_DECISION,
            ['title', 'summary', 'decision_content'],
        );
    }
}
