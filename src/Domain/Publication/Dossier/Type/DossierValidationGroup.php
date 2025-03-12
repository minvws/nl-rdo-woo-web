<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\Step\StepDefinitionInterface;

/**
 * These validation groups can be used when the validation of a property that is shared between multiple dossiertypes
 * needs to vary for a specific dossiertype. The naming convention is DOSSIERTYPE_STEPNAME.
 */
enum DossierValidationGroup: string
{
    // Based on StepName values for multiple dossier types
    case DETAILS = 'details';
    case DECISION = 'decision';
    case DOCUMENTS = 'documents';
    case PUBLICATION = 'publication';
    case CONTENT = 'content';

    // Specific combinations for DossierType + StepName
    case COVENANT_DETAILS = 'covenant_details';
    case ANNUAL_REPORT_DETAILS = 'annual_report_details';
    case INVESTIGATION_REPORT_DETAILS = 'investigation_report_details';
    case COMPLAINT_JUDGEMENT_DETAILS = 'complaint_judgement_details';
    case DISPOSITION_DETAILS = 'disposition_details';
    case OTHER_PUBLICATION_DETAILS = 'other_publication_details';
    case ADVICE_DETAILS = 'advice_details';

    /**
     * @return string[] Returns the values of applicable enum cases, as the validator cannot use enums as input
     */
    public static function getValidationGroupsForStep(StepDefinitionInterface $step): array
    {
        $groups = [
            self::from($step->getName()->value)->value,
        ];

        $typeSpecificGroup = self::tryFrom(
            sprintf(
                '%s_%s',
                str_replace('-', '_', $step->getDossierType()->value),
                $step->getName()->value,
            )
        );

        if ($typeSpecificGroup !== null) {
            $groups[] = $typeSpecificGroup->value;
        }

        return $groups;
    }
}
