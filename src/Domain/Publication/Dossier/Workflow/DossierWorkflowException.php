<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\Exception\TransitionException;

class DossierWorkflowException extends \RuntimeException
{
    public static function forTransitionFailed(
        AbstractDossier $dossier,
        DossierStatusTransition $transition,
        TransitionException $exception,
    ): self {
        if ($exception instanceof NotEnabledTransitionException) {
            $details = "\n";
            foreach ($exception->getTransitionBlockerList()->getIterator() as $item) {
                $details .= sprintf("- %s\n", $item->getMessage());
            }
        } else {
            $details = $exception->getMessage();
        }

        return new self(sprintf(
            'Could not apply transition %s to dossier %s: %s',
            $transition->value,
            $dossier->getId(),
            $details,
        ));
    }

    public static function forTransitionNotAllowed(
        AbstractDossier $dossier,
        DossierStatusTransition $transition,
    ): self {
        return new self(sprintf(
            'Transition %s is not allowed for dossier %s',
            $transition->value,
            $dossier->getId(),
        ));
    }

    public static function forCannotUpdatePublication(AbstractDossier $dossier): self
    {
        return new self(sprintf(
            'Cannot update publication for dossier %s',
            $dossier->getId(),
        ));
    }
}
