<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Command;

use App\Service\Security\AuditUserDetails;
use Symfony\Component\Uid\Uuid;

readonly class DeleteDossierCommand
{
    final public function __construct(
        public Uuid $dossierId,
        public AuditUserDetails $auditUserDetails,
        public bool $overrideWorkflow = false,
    ) {
    }
}
