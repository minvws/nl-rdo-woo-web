<?php

declare(strict_types=1);

namespace App\Domain\Organisation\Event;

use App\Entity\Organisation;
use MinVWS\AuditLogger\Contracts\LoggableUser;

readonly class OrganisationCreatedEvent
{
    public function __construct(
        public LoggableUser $actor,
        public Organisation $organisation,
    ) {
    }
}
