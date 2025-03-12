<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\Advice;

use App\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use Jaytaph\TypeArray\TypeArray;

readonly class AdviceSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private AdviceRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::ADVICE;
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::ADVICE,
        );
    }
}
