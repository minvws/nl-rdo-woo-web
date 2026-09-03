<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Prefix\PrefixMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Subject\SubjectPreviewUrlGenerator;

use function array_map;
use function array_values;

class OrganisationMapper
{
    /**
     * @param array<array-key,Organisation> $organisations
     *
     * @return list<OrganisationResponseDto>
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

    /**
     * @param array<array-key,Organisation> $organisations
     *
     * @return list<OrganisationDetailResponseDto>
     */
    public static function fromEntitiesWithDetail(
        array $organisations,
        SubjectPreviewUrlGenerator $previewUrlGenerator,
    ): array {
        return array_values(array_map(
            static fn (Organisation $organisation): OrganisationDetailResponseDto => self::fromEntityWithDetail($organisation, $previewUrlGenerator),
            $organisations,
        ));
    }

    public static function fromEntityWithDetail(
        Organisation $organisation,
        SubjectPreviewUrlGenerator $previewUrlGenerator,
    ): OrganisationDetailResponseDto {
        return new OrganisationDetailResponseDto(
            $organisation->getId(),
            $organisation->getName(),
            DepartmentMapper::fromEntities($organisation->getDepartments()->toArray()),
            SubjectMapper::fromEntities($organisation->getSubjects()->toArray(), $previewUrlGenerator),
            PrefixMapper::fromEntities($organisation->getDocumentPrefixes()->toArray()),
        );
    }
}
