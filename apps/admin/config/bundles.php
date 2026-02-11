<?php

declare(strict_types=1);

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Scheb\TwoFactorBundle\SchebTwoFactorBundle;

return [
    ApiPlatformBundle::class => ['all' => true],
    SchebTwoFactorBundle::class => ['all' => true],
];
