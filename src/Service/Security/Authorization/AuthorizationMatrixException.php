<?php

declare(strict_types=1);

namespace App\Service\Security\Authorization;

class AuthorizationMatrixException extends \RuntimeException
{
    public static function forUnknownFilter(AuthorizationMatrixFilter $filter): self
    {
        return new self(sprintf('Unknown authorization matrix filter "%s".', $filter->value));
    }

    public static function forNoActiveUser(): self
    {
        return new self('No active user to get active organisation for');
    }
}
