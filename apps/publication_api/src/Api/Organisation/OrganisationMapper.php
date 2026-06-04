<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use Shared\Domain\Organisation\Organisation;

use function array_map;
use function array_values;

class OrganisationMapper
{
    /**
     * @param array<array-key,Organisation> $organisations
     *
     * @return array<array-key,OrganisationResponseDto>
     */
    public static function fromEntities(array $organisations): array
    {
        return array_values(array_map(self::fromEntity(...), $organisations));
    }

    public static function fromEntity(Organisation $organisation): OrganisationResponseDto
    {
        return new OrganisationResponseDto(
            $organisation->getId(),
            $organisation->getName(),
        );
    }
}
