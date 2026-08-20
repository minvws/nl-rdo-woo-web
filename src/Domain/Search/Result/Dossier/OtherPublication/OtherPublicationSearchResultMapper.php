<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier\OtherPublication;

use MinVWS\TypeArray\TypeArray;
use Shared\ApplicationId;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;

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

    public function map(TypeArray $hit, ApplicationId $applicationId = ApplicationId::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::OTHER_PUBLICATION,
            applicationId: $applicationId,
        );
    }
}
