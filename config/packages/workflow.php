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
use Symfony\Component\DependencyInjection\Loader\Configurator\App;

/*
 * Workflow configuration using the Symfony 7.4+ array-based approach.
 *
 * Each workflow class provides its configuration via the getConfiguration() method,
 * which returns a complete array in the format expected by Symfony's workflow extension.
 */

return App::config([
    'framework' => [
        'workflows' => [
            WooDecisionWorkflow::WOO_DECISION_WORKFLOW_NAME => WooDecisionWorkflow::getConfiguration(),
            CovenantWorkflow::COVENANT_WORKFLOW_NAME => CovenantWorkflow::getConfiguration(),
            AnnualReportWorkflow::ANNUAL_REPORT_WORKFLOW_NAME => AnnualReportWorkflow::getConfiguration(),
            InvestigationReportWorkflow::INVESTIGATION_REPORT_WORKFLOW_NAME => InvestigationReportWorkflow::getConfiguration(),
            DispositionWorkflow::DISPOSITION_WORKFLOW_NAME => DispositionWorkflow::getConfiguration(),
            ComplaintJudgementWorkflow::COMPLAINT_JUDGEMENT_WORKFLOW_NAME => ComplaintJudgementWorkflow::getConfiguration(),
            OtherPublicationWorkflow::OTHER_PUBLICATION_WORKFLOW_NAME => OtherPublicationWorkflow::getConfiguration(),
            AdviceWorkflow::ADVICE_WORKFLOW_NAME => AdviceWorkflow::getConfiguration(),
            RequestForAdviceWorkflow::REQUEST_FOR_ADVICE_WORKFLOW_NAME => RequestForAdviceWorkflow::getConfiguration(),
        ],
    ]]);
