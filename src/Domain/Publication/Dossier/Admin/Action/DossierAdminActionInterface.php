<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Dossier admin actions are used on the /balie/admin/dossiers page to manually execute specific actions on dossiers.
 * For example triggering a re-index or regenerating download archives.
 *
 * Implementations should execute async for any action that isn't near-instant, as the user interface waits on the
 * result. A message is shown to the user that it may take some time to complete by default.
 * Usually this async execution can be easily implemented by just dispatching a command in the execute method.
 */
#[AutoconfigureTag('woo_platform.publication.dossier.admin.action')]
interface DossierAdminActionInterface
{
    public function getAdminAction(): DossierAdminAction;

    public function supports(AbstractDossier $dossier): bool;

    public function execute(AbstractDossier $dossier): void;

    public function needsConfirmation(): bool;
}
