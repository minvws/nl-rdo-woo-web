<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;

use function array_filter;
use function array_values;
use function str_starts_with;

enum DossierValidationGroup: string
{
    // Based on StepName values for multiple dossier types
    case DETAILS = 'details';
    case DECISION = 'decision';
    case DOCUMENTS = 'documents';
    case PUBLICATION = 'publication';
    case CONTENT = 'content';

    // Transition-level groups for workflow guards
    case WORKFLOW_PUBLISH = 'workflow_publish';
    case WORKFLOW_PUBLISH_AS_PREVIEW = 'workflow_publish_as_preview';
    case WORKFLOW_SCHEDULE_PUBLISH = 'workflow_schedule_publish';

    /**
     * @return array<array-key, self>
     */
    public static function getValidationGroupsForStepName(StepName $stepName): array
    {
        return match ($stepName) {
            StepName::DETAILS => [self::DETAILS],
            StepName::DECISION => [self::DECISION],
            StepName::DOCUMENTS => [self::DOCUMENTS],
            StepName::PUBLICATION => [self::PUBLICATION],
            StepName::CONTENT => [self::CONTENT],
        };
    }

    /**
     * @return array<array-key, self>
     */
    public static function getForWorkflowTransitions(DossierStatusTransition $dossierStatusTransition): array
    {
        return match ($dossierStatusTransition) {
            DossierStatusTransition::PUBLISH => [self::WORKFLOW_PUBLISH],
            DossierStatusTransition::PUBLISH_AS_PREVIEW => [self::WORKFLOW_PUBLISH_AS_PREVIEW],
            DossierStatusTransition::SCHEDULE_PUBLISH => [self::WORKFLOW_SCHEDULE_PUBLISH],
            default => [],
        };
    }

    /**
     * @return list<self::*>
     */
    public static function allNonWorkflowGroups(): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $group): bool => ! str_starts_with($group->value, 'workflow_'),
        ));
    }
}
