<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Subject;

use Symfony\Component\Uid\Uuid;

final readonly class IndexSubjectCommand
{
    public function __construct(
        private Uuid $uuid,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
