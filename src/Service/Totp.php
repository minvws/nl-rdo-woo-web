<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Security\User;

class Totp
{
    public function __construct(protected string $issuer)
    {
    }

    public function getTotpUri(User $user): string
    {
        $username = $user->getTotpAuthenticationUsername();
        $secret = $user->getMfaToken();

        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s', $username, $secret, $this->issuer);
    }
}
