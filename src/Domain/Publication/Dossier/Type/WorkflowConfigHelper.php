<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Symfony\Config\Framework\Workflows\WorkflowsConfig;

class WorkflowConfigHelper
{
    /**
     * @codeCoverageIgnore
     *
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
