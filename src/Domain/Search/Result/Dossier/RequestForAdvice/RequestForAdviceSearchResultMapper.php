<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier\RequestForAdvice;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

readonly class RequestForAdviceSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private RequestForAdviceRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::REQUEST_FOR_ADVICE;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::REQUEST_FOR_ADVICE,
            mode: $mode,
        );
    }
}
