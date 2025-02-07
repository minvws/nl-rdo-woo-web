<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WorkflowConfigHelper;
use App\Domain\Publication\Dossier\Workflow\DossierMarkingStore;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Config\FrameworkConfig;

/**
 * @codeCoverageIgnore
 */
class InvestigationReportWorkflow
{
    public static function configure(FrameworkConfig $framework): void
    {
        $workflow = $framework->workflows()->workflows('investigation_report_workflow');

        $workflow->type('state_machine')
            ->supports([InvestigationReport::class])
            ->initialMarking([DossierStatus::NEW->value]);

        $workflow->markingStore()
            ->service(DossierMarkingStore::class);

        $workflow->place()->name(DossierStatus::NEW->value);
        $workflow->place()->name(DossierStatus::CONCEPT->value);
        $workflow->place()->name(DossierStatus::SCHEDULED->value);
        $workflow->place()->name(DossierStatus::PUBLISHED->value);
        $workflow->place()->name(DossierStatus::DELETED->value);

        $workflow->transition()
            ->name(DossierStatusTransition::UPDATE_DETAILS->value)
            ->from(DossierStatus::NEW->value)
            ->to(DossierStatus::CONCEPT->value);

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_DETAILS,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_CONTENT,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_MAIN_DOCUMENT,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PUBLISHED,
            ],
        );

        $workflow->transition()
            ->name(DossierStatusTransition::DELETE_MAIN_DOCUMENT->value)
            ->from(DossierStatus::CONCEPT->value)
            ->to(DossierStatus::CONCEPT->value);

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_ATTACHMENT,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::DELETE_ATTACHMENT,
            [
                DossierStatus::CONCEPT,
                DossierStatus::DELETED,
            ],
        );

        $workflow->transition()
            ->name(DossierStatusTransition::DELETE->value)
            ->from(DossierStatus::CONCEPT->value)
            ->to(DossierStatus::DELETED->value);

        $workflow->transition()
            ->name(DossierStatusTransition::SCHEDULE_PUBLISH->value)
            ->from([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value])
            ->to(DossierStatus::SCHEDULED->value);

        $workflow->transition()
            ->name(DossierStatusTransition::PUBLISH->value)
            ->from([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value])
            ->to(DossierStatus::PUBLISHED->value);
    }
}
