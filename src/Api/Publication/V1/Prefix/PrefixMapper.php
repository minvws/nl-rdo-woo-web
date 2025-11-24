<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Prefix;

use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;

class PrefixMapper
{
    /**
     * @param array<array-key,DocumentPrefix> $documentPrefixes
     *
     * @return array<array-key,PrefixDto>
     */
    public static function fromEntities(array $documentPrefixes): array
    {
        return array_map(self::fromEntity(...), $documentPrefixes);
    }

    public static function fromEntity(DocumentPrefix $documentPrefix): PrefixDto
    {
        return new PrefixDto(
            $documentPrefix->getId(),
            OrganisationReferenceDto::fromEntity($documentPrefix->getOrganisation()),
            $documentPrefix->getPrefix(),
        );
    }

    public static function fromCreateDto(PrefixCreateDto $prefixCreateDto, Organisation $organisation): DocumentPrefix
    {
        $documentPrefix = new DocumentPrefix();
        $documentPrefix->setPrefix($prefixCreateDto->prefix);
        $documentPrefix->setOrganisation($organisation);

        return $documentPrefix;
    }
}
