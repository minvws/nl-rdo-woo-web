<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Service\Security\ApplicationMode\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class WooDecisionSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private WooDecisionRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::WOO_DECISION;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::WOO_DECISION,
            mode: $mode,
        );
    }
}
