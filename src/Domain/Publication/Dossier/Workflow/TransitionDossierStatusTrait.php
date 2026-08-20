<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Workflow\Transition;
use Webmozart\Assert\Assert;

trait TransitionDossierStatusTrait
{
    private function getOldState(Transition $transition): DossierStatus
    {
        $asString = $transition->getFroms()[0] ?? '';
        Assert::string($asString);

        $dossierStatus = DossierStatus::tryFrom($asString);
        Assert::isInstanceOf($dossierStatus, DossierStatus::class, 'OldState contained an invalid state');

        return $dossierStatus;
    }

    private function getNewState(Transition $transition): DossierStatus
    {
        $asString = $transition->getTos()[0] ?? '';
        Assert::string($asString);

        $dossierStatus = DossierStatus::tryFrom($asString);
        Assert::isInstanceOf($dossierStatus, DossierStatus::class, 'NewState contained an invalid state');

        return $dossierStatus;
    }
}
