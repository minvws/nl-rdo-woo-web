<?php

declare(strict_types=1);

namespace Shared\Domain\Organisation\Event;

use MinVWS\AuditLogger\Contracts\LoggableUser;
use Shared\Domain\Organisation\Organisation;

readonly class OrganisationCreatedEvent
{
    public function __construct(
        public LoggableUser $actor,
        public Organisation $organisation,
    ) {
    }
}
