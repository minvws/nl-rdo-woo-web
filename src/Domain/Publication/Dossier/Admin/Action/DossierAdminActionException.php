<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin\Action;

use Shared\Domain\Publication\Dossier\AbstractDossier;

class DossierAdminActionException extends \RuntimeException
{
    public static function forActionNotAvailable(AbstractDossier $dossier, DossierAdminAction $adminAction): self
    {
        return new self(sprintf(
            'Cannot execute admin action %s on dossier with id %s',
            $adminAction->value,
            $dossier->getId()->toBase58(),
        ));
    }
}
