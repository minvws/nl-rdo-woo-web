<?php

declare(strict_types=1);

namespace Shared\Domain\Organisation\Event;

use Doctrine\ORM\PersistentCollection;
use MinVWS\AuditLogger\Contracts\LoggableUser;
use Shared\Domain\Organisation\Organisation;

readonly class OrganisationUpdatedEvent
{
    public function __construct(
        public LoggableUser $actor,
        public Organisation $organisation,
        /** @var array<string, PersistentCollection|list<mixed>> $changes */
        public array $changes,
    ) {
    }
}
