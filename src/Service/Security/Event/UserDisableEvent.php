<?php

declare(strict_types=1);

namespace App\Service\Security\Event;

use App\Entity\User;
use MinVWS\AuditLogger\Contracts\LoggableUser;

readonly class UserDisableEvent
{
    public function __construct(
        public User $user,
        public LoggableUser $actor,
    ) {
    }
}
