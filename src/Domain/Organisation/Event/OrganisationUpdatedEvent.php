<?php

declare(strict_types=1);

namespace App\Domain\Organisation\Event;

use App\Domain\Organisation\Organisation;
use Doctrine\ORM\PersistentCollection;
use MinVWS\AuditLogger\Contracts\LoggableUser;

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
