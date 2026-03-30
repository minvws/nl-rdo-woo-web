<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Workflow\DossierMarkingStore;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;

/**
 * @codeCoverageIgnore
 */
class CovenantWorkflow
{
    public const string COVENANT_WORKFLOW_NAME = 'covenant_workflow';

    /**
     * @return array<string, mixed>
     */
    public static function getConfiguration(): array
    {
        return [
            'type' => 'state_machine',
            'supports' => [Covenant::class],
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
            DossierStatus::NEW->value,
            DossierStatus::CONCEPT->value,
            DossierStatus::SCHEDULED->value,
            DossierStatus::PUBLISHED->value,
            DossierStatus::DELETED->value,
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
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_DETAILS->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_CONTENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_CONTENT->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_ATTACHMENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PUBLISHED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::UPDATE_ATTACHMENT->value,
                'from' => $status->value,
                'to' => $status->value,
            ];
        }

        // Non-moving UPDATE_MAIN_DOCUMENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::SCHEDULED, DossierStatus::PUBLISHED] as $status) {
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

        // Non-moving DELETE_ATTACHMENT transitions
        foreach ([DossierStatus::CONCEPT, DossierStatus::DELETED] as $status) {
            $transitions[] = [
                'name' => DossierStatusTransition::DELETE_ATTACHMENT->value,
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

        // SCHEDULE_PUBLISH from multiple places
        foreach ([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value] as $from) {
            $transitions[] = [
                'name' => DossierStatusTransition::SCHEDULE_PUBLISH->value,
                'from' => $from,
                'to' => DossierStatus::SCHEDULED->value,
            ];
        }

        // PUBLISH from multiple places
        foreach ([DossierStatus::CONCEPT->value, DossierStatus::SCHEDULED->value] as $from) {
            $transitions[] = [
                'name' => DossierStatusTransition::PUBLISH->value,
                'from' => $from,
                'to' => DossierStatus::PUBLISHED->value,
            ];
        }

        return $transitions;
    }
}
