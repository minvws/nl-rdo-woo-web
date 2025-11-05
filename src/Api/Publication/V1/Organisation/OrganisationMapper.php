<?php

declare(strict_types=1);

namespace App\Api\Publication\V1\Organisation;

use App\Domain\Organisation\Organisation;

class OrganisationMapper
{
    /**
     * @param array<array-key,Organisation> $organisations
     *
     * @return array<array-key,OrganisationDto>
     */
    public static function fromEntities(array $organisations): array
    {
        return array_map(fn (Organisation $organisation): OrganisationDto => self::fromEntity($organisation), $organisations);
    }

    public static function fromEntity(Organisation $organisation): OrganisationDto
    {
        return new OrganisationDto(
            $organisation->getId(),
            $organisation->getName(),
        );
    }
}
