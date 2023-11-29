<?php

declare(strict_types=1);

namespace App\Service\Stats\Handler;

interface StatsHandlerInterface
{
    public function store(\DateTimeImmutable $dt, string $hostname, string $section, int $duration): void;
}
