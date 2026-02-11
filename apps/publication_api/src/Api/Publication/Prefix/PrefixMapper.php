<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Prefix;

use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;

use function array_map;
use function array_values;

class PrefixMapper
{
    /**
     * @param array<array-key,DocumentPrefix> $documentPrefixes
     *
     * @return array<array-key,PrefixDto>
     */
    public static function fromEntities(array $documentPrefixes): array
    {
        return array_values(array_map(self::fromEntity(...), $documentPrefixes));
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
