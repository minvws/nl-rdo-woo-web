<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use Symfony\Component\Uid\Uuid;

final class OrganisationRequestDto
{
    final public function __construct(
        public Uuid $id,
        public string $name,
    ) {
    }
}
