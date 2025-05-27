<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\AnnualReport;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Enum\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class AnnualReportSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private AnnualReportRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::ANNUAL_REPORT;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::ANNUAL_REPORT,
            mode: $mode,
        );
    }
}
