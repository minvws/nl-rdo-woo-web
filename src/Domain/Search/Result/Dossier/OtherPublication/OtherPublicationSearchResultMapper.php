<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\OtherPublication;

use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Enum\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class OtherPublicationSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private OtherPublicationRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::OTHER_PUBLICATION;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::OTHER_PUBLICATION,
            mode: $mode,
        );
    }
}
