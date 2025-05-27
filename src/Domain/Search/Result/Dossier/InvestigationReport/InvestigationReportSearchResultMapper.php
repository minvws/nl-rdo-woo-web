<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\Dossier\InvestigationReport;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use App\Domain\Search\Result\ResultEntryInterface;
use App\Domain\Search\Result\SearchResultMapperInterface;
use App\Enum\ApplicationMode;
use MinVWS\TypeArray\TypeArray;

readonly class InvestigationReportSearchResultMapper implements SearchResultMapperInterface
{
    public function __construct(
        private DossierSearchResultBaseMapper $baseMapper,
        private InvestigationReportRepository $repository,
    ) {
    }

    public function supports(ElasticDocumentType $type): bool
    {
        return $type === ElasticDocumentType::INVESTIGATION_REPORT;
    }

    public function map(TypeArray $hit, ApplicationMode $mode = ApplicationMode::PUBLIC): ?ResultEntryInterface
    {
        return $this->baseMapper->map(
            $hit,
            $this->repository,
            ElasticDocumentType::INVESTIGATION_REPORT,
            mode: $mode,
        );
    }
}
