<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\AuditLog;

use MinVWS\AuditLogger\Events\Logging\GeneralLogEvent;

class DossierDeleteLogEvent extends GeneralLogEvent
{
    public const string EVENT_CODE = 'woo_1';
    public const string EVENT_KEY = 'dossier_deleted';
}
