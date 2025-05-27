<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\Disposition;

use App\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Enum\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class DispositionSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private DispositionRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::DISPOSITION;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::DISPOSITION,
            mode: $mode,
        );
    }
}
