<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use PublicationApi\Api\Organisation\OrganisationResponseDto;
use Symfony\Component\Uid\Uuid;

final class PrefixResponseDto
{
    final public function __construct(
        public Uuid $id,
        public OrganisationResponseDto $organisation,
        public string $prefix,
    ) {
    }
}
