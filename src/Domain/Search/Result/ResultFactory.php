<?php

declare(strict_types=1);

namespace App\Domain\Search\Result;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\AnnualReport\AnnualReportSearchResultMapper;
use App\Domain\Search\Result\ComplaintJudgement\ComplaintJudgementSearchResultMapper;
use App\Domain\Search\Result\Covenant\CovenantSearchResultMapper;
use App\Domain\Search\Result\Disposition\DispositionSearchResultMapper;
use App\Domain\Search\Result\InvestigationReport\InvestigationReportSearchResultMapper;
use App\Domain\Search\Result\WooDecision\DocumentSearchResultMapper;
use App\Domain\Search\Result\WooDecision\WooDecisionSearchResultMapper;
use Jaytaph\TypeArray\TypeArray;

readonly class ResultFactory
{
    public function __construct(
        private WooDecisionSearchResultMapper $wooDecisionMapper,
        private DocumentSearchResultMapper $documentMapper,
        private CovenantSearchResultMapper $covenantMapper,
        private AnnualReportSearchResultMapper $annualReportMapper,
        private InvestigationReportSearchResultMapper $investigationReportMapper,
        private DispositionSearchResultMapper $dispositionSearchResultMapper,
        private ComplaintJudgementSearchResultMapper $complaintJudgementResultMapper,
    ) {
    }

    public function map(TypeArray $hit): ?ResultEntryInterface
    {
        $type = ElasticDocumentType::from($hit->getString('[fields][type][0]'));

        return match ($type) {
            ElasticDocumentType::WOO_DECISION => $this->wooDecisionMapper->map($hit),
            ElasticDocumentType::WOO_DECISION_DOCUMENT => $this->documentMapper->map($hit),
            ElasticDocumentType::COVENANT => $this->covenantMapper->map($hit),
            ElasticDocumentType::ANNUAL_REPORT => $this->annualReportMapper->map($hit),
            ElasticDocumentType::INVESTIGATION_REPORT => $this->investigationReportMapper->map($hit),
            ElasticDocumentType::DISPOSITION => $this->dispositionSearchResultMapper->map($hit),
            ElasticDocumentType::COMPLAINT_JUDGEMENT => $this->complaintJudgementResultMapper->map($hit),
        };
    }
}
