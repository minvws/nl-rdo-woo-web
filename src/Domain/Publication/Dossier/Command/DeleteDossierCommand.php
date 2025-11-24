<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Command;

use Shared\Service\Security\AuditUserDetails;
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
