<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Uid\Uuid;

final readonly class OrganisationResponseDto
{
    public function __construct(
        #[ApiProperty(description: 'The unique identifier of the organisation.')]
        public Uuid $id,
        #[ApiProperty(description: 'The name of the organisation.')]
        public string $name,
    ) {
    }
}
