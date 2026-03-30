<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Workflow\DossierMarkingStore;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;

/**
 * @codeCoverageIgnore
 */
class WooDecisionWorkflow
{
    public const string WOO_DECISION_WORKFLOW_NAME = 'woo_decision_workflow';

    /**
     * @return array<string, mixed>
     */
    public static function getConfiguration(): array
    {
        return [
            'type' => 'state_machine',
            'supports' => [WooDecision::class],
            'initial_marking' => [DossierStatus::NEW->value],
            'marking_store' => [
                'service' => DossierMarkingStore::class,
            ],
            'places' => self::getPlaces(),
            'transitions' => self::getTransitions(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function getPlaces(): array
    {
        return [
            DossierStatus::NEW->value => [],
            DossierStatus::CONCEPT->value => [],
            DossierStatus::SCHEDULED->value => [],
            DossierStatus::PREVIEW->value => [],
            DossierStatus::PUBLISHED->value => [],
            DossierStatus::DELETED->value => [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getTransitions(): array
    {
        $transitions = [];

        // Initial transition from NEW to CONCEPT
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_DETAILS->value,
            'from' => DossierStatus::NEW->value,
            'to' => DossierStatus::CONCEPT->value,
        ];

        // Non-moving UPDATE_DETAILS transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_DETAILS->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_DECISION transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_DECISION->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_MAIN_DOCUMENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_MAIN_DOCUMENT->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // DELETE_MAIN_DOCUMENT (non-moving)
        $transitions[] = [
            'name' => DossierStatusTransition::DELETE_MAIN_DOCUMENT->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];

        // Non-moving UPDATE_ATTACHMENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_ATTACHMENT->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_PRODUCTION_REPORT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_PRODUCTION_REPORT->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_DOCUMENTS transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PREVIEW, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_DOCUMENTS->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // DELETE transition
        $transitions[] = [
            'name' => DossierStatusTransition::DELETE->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::DELETED->value,
        ];

        // SCHEDULE_PUBLISH_AS_PREVIEW from multiple places
        foreach ([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value] as $from) {
            $transitions[] = [
                'name' => DossierStatusTransition::SCHEDULE_PUBLISH_AS_PREVIEW->value,
                'from' => $from,
                'to' => DossierStatus::SCHEDULED->value,
            ];
        }

        // SCHEDULE_PUBLISH from multiple places
        foreach ([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value] as $from) {
            $transitions[] = [
                'name' => DossierStatusTransition::SCHEDULE_PUBLISH->value,
                'from' => $from,
                'to' => DossierStatus::SCHEDULED->value,
            ];
        }

        // SCHEDULE_PUBLISH from PREVIEW (non-moving)
        $transitions[] = [
            'name' => DossierStatusTransition::SCHEDULE_PUBLISH->value,
            'from' => DossierStatus::PREVIEW->value,
            'to' => DossierStatus::PREVIEW->value,
        ];

        // PUBLISH_AS_PREVIEW from multiple places
        foreach ([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value] as $from) {
            $transitions[] = [
                'name' => DossierStatusTransition::PUBLISH_AS_PREVIEW->value,
                'from' => $from,
                'to' => DossierStatus::PREVIEW->value,
            ];
        }

        // PUBLISH from multiple places
        foreach ([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value, DossierStatus::PREVIEW->value] as $from) {
            $transitions[] = [
                'name' => DossierStatusTransition::PUBLISH->value,
                'from' => $from,
                'to' => DossierStatus::PUBLISHED->value,
            ];
        }

        // Non-moving DELETE_ATTACHMENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::DELETED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::DELETE_ATTACHMENT->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        return $transitions;
    }
}
