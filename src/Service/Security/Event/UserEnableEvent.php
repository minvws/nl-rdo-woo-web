<?php

declare(strict_types=1);

namespace Shared\Service\Security\Event;

use MinVWS\AuditLogger\Contracts\LoggableUser;
use Shared\Service\Security\User;

readonly class UserEnableEvent
{
    public function __construct(
        public User $user,
        public LoggableUser $actor,
    ) {
    }
}
