<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier\ComplaintJudgement;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

readonly class ComplaintJudgementSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private ComplaintJudgementRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::COMPLAINT_JUDGEMENT;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::COMPLAINT_JUDGEMENT,
            mode: $mode,
        );
    }
}
