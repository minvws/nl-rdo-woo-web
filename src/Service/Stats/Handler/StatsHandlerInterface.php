<?php

declare(strict_types=1);

namespace Shared\Service\Stats\Handler;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('woo_platform.stats.handler')]
interface StatsHandlerInterface
{
    public function store(DateTimeImmutable $dt, string $hostname, string $section, int $duration): void;
}
