<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Config\Framework\Workflows\WorkflowsConfig;

class WorkflowConfigHelper
{
    /**
     * @param DossierStatus[] $places
     */
    public static function defineNonMovingTransitions(
        WorkflowsConfig $workflow,
        DossierStatusTransition $transition,
        array $places,
    ): void {
        foreach ($places as $place) {
            $workflow->transition()
                ->name($transition->value)
                ->from($place->value)
                ->to($place->value);
        }
    }
}
