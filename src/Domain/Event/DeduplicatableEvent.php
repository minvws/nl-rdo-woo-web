<?php

declare(strict_types=1);

namespace Shared\Domain\Event;

interface DeduplicatableEvent
{
    public function deduplicationKey(): string;
}
