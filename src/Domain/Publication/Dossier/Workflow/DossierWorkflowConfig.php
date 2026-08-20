<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;

/**
 * @phpstan-type Transition array{name: value-of<DossierStatusTransition>, from: value-of<DossierStatus>, to: value-of<DossierStatus>}
 * @phpstan-type Transitions array<int,Transition>
 * @phpstan-type Places array<int,value-of<DossierStatus>>
 * @phpstan-type Config array{
 *  type: string,
 *  supports: list<class-string<AbstractDossier>>,
 *  initial_marking: list<value-of<DossierStatus>>,
 *  marking_store: array{service: class-string<MarkingStoreInterface>},
 *  places: Places,
 *  transitions: Transitions
 * }
 */
interface DossierWorkflowConfig
{
    /**
     * @return Config
     */
    public static function getConfiguration(): array;
}
