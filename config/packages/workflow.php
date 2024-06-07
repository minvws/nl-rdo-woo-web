<?php

declare(strict_types=1);

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportWorkflow;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementWorkflow;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantWorkflow;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionWorkflow;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportWorkflow;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionWorkflow;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig): void {
    WooDecisionWorkflow::configure($frameworkConfig);
    CovenantWorkflow::configure($frameworkConfig);
    AnnualReportWorkflow::configure($frameworkConfig);
    InvestigationReportWorkflow::configure($frameworkConfig);
    DispositionWorkflow::configure($frameworkConfig);
    ComplaintJudgementWorkflow::configure($frameworkConfig);
};
