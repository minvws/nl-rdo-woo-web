<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\Dossier\InvestigationReport;

use MinVWS\TypeArray\TypeArray;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\Dossier\DossierSearchResultBaseMapper;
use Shared\Domain\Search\Result\ResultEntryInterface;
use Shared\Domain\Search\Result\SearchResultMapperInterface;
use Shared\Service\Security\ApplicationMode\ApplicationMode;

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
