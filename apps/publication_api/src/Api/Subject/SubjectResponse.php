<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use Symfony\Component\Uid\Uuid;

final readonly class SubjectResponse
{
    public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
