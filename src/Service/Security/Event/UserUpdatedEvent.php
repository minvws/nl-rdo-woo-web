<?php

declare(strict_types=1);

namespace Shared\Service\Security\Event;

use MinVWS\AuditLogger\Contracts\LoggableUser;
use Shared\Service\Security\User;

readonly class UserUpdatedEvent
{
    public function __construct(
        public User $oldUser,
        public User $updatedUser,
        public LoggableUser $actor,
    ) {
    }
}
