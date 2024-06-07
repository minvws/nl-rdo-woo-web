<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WorkflowConfigHelper;
use App\Domain\Publication\Dossier\Workflow\DossierMarkingStore;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Config\FrameworkConfig;

class WooDecisionWorkflow
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function configure(FrameworkConfig $framework): void
    {
        $workflow = $framework->workflows()->workflows('woo_decision_workflow');

        $workflow->type('state_machine')
            ->supports([WooDecision::class])
            ->initialMarking([DossierStatus::NEW->value]);

        $workflow->markingStore()
            ->service(DossierMarkingStore::class);

        $workflow->place()->name(DossierStatus::NEW->value);
        $workflow->place()->name(DossierStatus::CONCEPT->value);
        $workflow->place()->name(DossierStatus::SCHEDULED->value);
        $workflow->place()->name(DossierStatus::PREVIEW->value);
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
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_DECISION,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_DECISION_DOCUMENT,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_ATTACHMENT,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_INVENTORY,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        );

        WorkflowConfigHelper::defineNonMovingTransitions(
            $workflow,
            DossierStatusTransition::UPDATE_DOCUMENTS,
            [
                DossierStatus::CONCEPT,
                DossierStatus::SCHEDULED,
                DossierStatus::PREVIEW,
                DossierStatus::PUBLISHED,
            ],
        );

        $workflow->transition()
            ->name(DossierStatusTransition::DELETE->value)
            ->from(DossierStatus::CONCEPT->value)
            ->to(DossierStatus::DELETED->value);

        $workflow->transition()
            ->name(DossierStatusTransition::SCHEDULE->value)
            ->from([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value])
            ->to(DossierStatus::SCHEDULED->value);

        $workflow->transition()
            ->name(DossierStatusTransition::PUBLISH_AS_PREVIEW->value)
            ->from([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value])
            ->to(DossierStatus::PREVIEW->value);

        $workflow->transition()
            ->name(DossierStatusTransition::PUBLISH->value)
            ->from([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value, DossierStatus::PREVIEW->value])
            ->to(DossierStatus::PUBLISHED->value);

        $workflow->transition()
            ->name(DossierStatusTransition::DELETE_ATTACHMENT->value)
            ->from(DossierStatus::CONCEPT->value)
            ->to(DossierStatus::CONCEPT->value);
    }
}
