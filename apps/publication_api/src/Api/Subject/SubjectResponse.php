<?php

declare(strict_types=1);

namespace PublicationApi\Api\Subject;

use PublicationApi\Api\Organisation\OrganisationResponseDto;
use Symfony\Component\Uid\Uuid;

final class SubjectResponse
{
    final public function __construct(
        public Uuid $id,
        public OrganisationResponseDto $organisation,
        public string $name,
    ) {
    }
}
