<?php

declare(strict_types=1);

use Shared\Domain\Publication\Dossier\Type\Advice\AdviceWorkflow;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportWorkflow;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementWorkflow;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantWorkflow;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionWorkflow;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportWorkflow;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationWorkflow;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceWorkflow;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionWorkflow;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig): void {
    WooDecisionWorkflow::configure($frameworkConfig);
    CovenantWorkflow::configure($frameworkConfig);
    AnnualReportWorkflow::configure($frameworkConfig);
    InvestigationReportWorkflow::configure($frameworkConfig);
    DispositionWorkflow::configure($frameworkConfig);
    ComplaintJudgementWorkflow::configure($frameworkConfig);
    OtherPublicationWorkflow::configure($frameworkConfig);
    AdviceWorkflow::configure($frameworkConfig);
    RequestForAdviceWorkflow::configure($frameworkConfig);
};
