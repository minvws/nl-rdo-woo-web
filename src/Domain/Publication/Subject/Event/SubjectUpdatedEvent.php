<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject\Event;

use Shared\Domain\Publication\Subject\Subject;
use Symfony\Component\Uid\Uuid;

readonly class SubjectUpdatedEvent
{
    public function __construct(
        private Uuid $uuid,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forSubject(Subject $subject): self
    {
        return new self($subject->getId());
    }
}
