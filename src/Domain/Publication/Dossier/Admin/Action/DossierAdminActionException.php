<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\AbstractDossier;

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
