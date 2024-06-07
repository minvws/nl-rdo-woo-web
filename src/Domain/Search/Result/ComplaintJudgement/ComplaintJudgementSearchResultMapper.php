<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\ComplaintJudgement;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\DossierTypeSearchResultMapper;
use App\Domain\Search\Result\DossierTypeSearchResultMapperInterface;
use App\Domain\Search\Result\ResultEntryInterface;
use Jaytaph\TypeArray\TypeArray;

readonly class ComplaintJudgementSearchResultMapper implements DossierTypeSearchResultMapperInterface
{
    public function __construct(
        private DossierTypeSearchResultMapper $baseMapper,
        private ComplaintJudgementRepository $repository,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::COMPLAINT_JUDGEMENT,
        );
    }
}
