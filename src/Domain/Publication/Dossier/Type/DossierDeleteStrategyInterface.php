<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A dossier delete strategy is called for dossier that is being deleted based on autowiring by the interface.
 * If additional checks are needed you should implement something like an instanceof check and return early when a
 * dossier type is not supported.
 *
 * A strategy can be implemented for one specific dossiertype (see WooDecisionDeleteStrategy) as an example, or target
 * a feature shared by multiple dossiertypes (see AttachmentDeleteStrategy).
 *
 * Important: it must NOT delete the dossier entity itself or its related entities! This will be handled after all
 * delete strategies have been executed. For this to work correctly you must ensure that cascade deletes are correctly
 * configured on each dossier type entity and sub-entities.
 */
#[AutoconfigureTag('woo_platform.publication.dossier_delete_strategy')]
interface DossierDeleteStrategyInterface
{
    public function delete(AbstractDossier $dossier): void;
}
