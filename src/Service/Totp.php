<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;

class Totp
{
    protected string $issuer;

    public function __construct(string $issuer)
    {
        $this->issuer = $issuer;
    }

    public function getTotpUri(User $user): string
    {
        $username = $user->getTotpAuthenticationUsername();
        $secret = $user->getMfaToken();

        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s', $username, $secret, $this->issuer);
    }
}
