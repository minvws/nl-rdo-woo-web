<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\Type\Advice\AdviceWorkflow;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportWorkflow;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementWorkflow;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantWorkflow;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionWorkflow;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionWorkflow;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportWorkflow;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationWorkflow;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceWorkflow;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionWorkflow;

use function array_map;
use function array_reduce;

/**
 * @phpstan-import-type Config from DossierWorkflowConfig
 */
enum DossierWorkflow: string
{
    case ADVICE = 'advice_workflow';
    case ANNUAL_REPORT = 'annual_report_workflow';
    case COMPLAINT_JUDGEMENT = 'complaint_judgement_workflow';
    case COVENANT = 'covenant_workflow';
    case DISPOSITION = 'disposition_workflow';
    case DRAFT_DECISION = 'draft_decision_workflow';
    case INVESTIGATION_REPORT = 'investigation_report_workflow';
    case OTHER_PUBLICATION = 'other_publication_workflow';
    case REQUEST_FOR_ADVICE = 'request_for_advice_workflow';
    case WOO_DECISION = 'woo_decision_workflow';

    /**
     * @return list<value-of<static>>
     */
    public static function all(): array
    {
        return array_map(static fn (DossierWorkflow $case): string => $case->value, static::cases());
    }

    /**
     * @return array<value-of<static>,Config>
     */
    public static function getConfigs(): array
    {
        /** @var array<value-of<static>,Config> */
        return array_reduce(
            static::cases(),
            static function (array $carry, DossierWorkflow $case): array {
                $carry[$case->value] = $case->getConfigClass()::getConfiguration();

                return $carry;
            },
            [],
        );
    }

    /**
     * @return class-string<DossierWorkflowConfig>
     */
    public function getConfigClass(): string
    {
        return match ($this) {
            self::ADVICE => AdviceWorkflow::class,
            self::ANNUAL_REPORT => AnnualReportWorkflow::class,
            self::COMPLAINT_JUDGEMENT => ComplaintJudgementWorkflow::class,
            self::COVENANT => CovenantWorkflow::class,
            self::DISPOSITION => DispositionWorkflow::class,
            self::DRAFT_DECISION => DraftDecisionWorkflow::class,
            self::INVESTIGATION_REPORT => InvestigationReportWorkflow::class,
            self::OTHER_PUBLICATION => OtherPublicationWorkflow::class,
            self::REQUEST_FOR_ADVICE => RequestForAdviceWorkflow::class,
            self::WOO_DECISION => WooDecisionWorkflow::class,
        };
    }
}
