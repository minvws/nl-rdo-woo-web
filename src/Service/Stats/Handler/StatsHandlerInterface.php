<?php

declare(strict_types=1);

namespace App\Service\Stats\Handler;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.stats.handler')]
interface StatsHandlerInterface
{
    public function store(\DateTimeImmutable $dt, string $hostname, string $section, int $duration): void;
}
