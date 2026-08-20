<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Step\StepName;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;

use function in_array;

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

    // Condition-level guard group: enforced only while the owning dossier is in a locked status
    case PUBLICATION_LOCKED = 'publication_locked';

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
     * @return array<array-key, self>
     */
    public static function getForStatus(DossierStatus $dossierStatus): array
    {
        $validationGroups = self::allNonWorkflowGroups();

        if (in_array($dossierStatus, DossierStatus::nonConceptCases(), true)) {
            $validationGroups[] = self::PUBLICATION_LOCKED;
        }

        return $validationGroups;
    }

    /**
     * @return list<self::*>
     */
    public static function allNonWorkflowGroups(): array
    {
        return [
            self::DETAILS,
            self::DECISION,
            self::DOCUMENTS,
            self::PUBLICATION,
            self::CONTENT,
        ];
    }
}
