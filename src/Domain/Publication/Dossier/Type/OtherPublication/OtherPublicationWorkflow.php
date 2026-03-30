<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\OtherPublication;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Workflow\DossierMarkingStore;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;

/**
 * @codeCoverageIgnore
 */
class OtherPublicationWorkflow
{
    public const string OTHER_PUBLICATION_WORKFLOW_NAME = 'other_publication_workflow';

    /**
     * @return array<string, mixed>
     */
    public static function getConfiguration(): array
    {
        return [
            'type' => 'state_machine',
            'supports' => [OtherPublication::class],
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
            DossierStatus::PUBLISHED->value => [],
            DossierStatus::DELETED->value => [],        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getTransitions(): array
    {
        $transitions = [];

        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_DETAILS->value,
            'from' => DossierStatus::NEW->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_DETAILS->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_DETAILS->value,
            'from' => DossierStatus::SCHEDULED->value,
            'to' => DossierStatus::SCHEDULED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_DETAILS->value,
            'from' => DossierStatus::PUBLISHED->value,
            'to' => DossierStatus::PUBLISHED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_CONTENT->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_CONTENT->value,
            'from' => DossierStatus::SCHEDULED->value,
            'to' => DossierStatus::SCHEDULED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_CONTENT->value,
            'from' => DossierStatus::PUBLISHED->value,
            'to' => DossierStatus::PUBLISHED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_MAIN_DOCUMENT->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_MAIN_DOCUMENT->value,
            'from' => DossierStatus::SCHEDULED->value,
            'to' => DossierStatus::SCHEDULED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_MAIN_DOCUMENT->value,
            'from' => DossierStatus::PUBLISHED->value,
            'to' => DossierStatus::PUBLISHED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::DELETE_MAIN_DOCUMENT->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_ATTACHMENT->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_ATTACHMENT->value,
            'from' => DossierStatus::SCHEDULED->value,
            'to' => DossierStatus::SCHEDULED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::UPDATE_ATTACHMENT->value,
            'from' => DossierStatus::PUBLISHED->value,
            'to' => DossierStatus::PUBLISHED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::DELETE_ATTACHMENT->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::CONCEPT->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::DELETE_ATTACHMENT->value,
            'from' => DossierStatus::DELETED->value,
            'to' => DossierStatus::DELETED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::DELETE->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::DELETED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::SCHEDULE_PUBLISH->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::SCHEDULED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::SCHEDULE_PUBLISH->value,
            'from' => DossierStatus::SCHEDULED->value,
            'to' => DossierStatus::SCHEDULED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::PUBLISH->value,
            'from' => DossierStatus::CONCEPT->value,
            'to' => DossierStatus::PUBLISHED->value,
        ];
        $transitions[] = [
            'name' => DossierStatusTransition::PUBLISH->value,
            'from' => DossierStatus::SCHEDULED->value,
            'to' => DossierStatus::PUBLISHED->value,
        ];

        return $transitions;
    }
}
