<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Security\AuditLog;

use MinVWS\AuditLogger\Events\Logging\GeneralLogEvent;

use function sprintf;

class LoginFailedAuditLogEvent extends GeneralLogEvent
{
    public function __construct(string $message)
    {
        parent::__construct();

        $this->failed = true;
        $this->failedReason = sprintf('publication_api_%s', $message);
    }
}
