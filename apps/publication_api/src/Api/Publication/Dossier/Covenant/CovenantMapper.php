<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class CovenantMapper
{
    /**
     * @param array<array-key,Covenant> $covenants
     *
     * @return list<CovenantDto>
     */
    public static function fromEntities(array $covenants): array
    {
        return array_values(array_map(
            self::fromEntity(...),
            $covenants,
        ));
    }

    public static function fromEntity(Covenant $covenant): CovenantDto
    {
        $mainDocument = $covenant->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $covenant->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $covenant->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new CovenantDto(
            $covenant->getId(),
            $covenant->getExternalId(),
            OrganisationReferenceDto::fromEntity($covenant->getOrganisation()),
            $covenant->getDocumentPrefix(),
            $covenant->getDossierNr(),
            $covenant->getInternalReference(),
            $covenant->getTitle(),
            $covenant->getSummary(),
            $covenant->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $covenant->getPublicationDate(),
            $covenant->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($covenant->getAttachments()->toArray()),
            $dateFrom,
            $covenant->getDateTo(),
            $covenant->getPreviousVersionLink(),
            $covenant->getParties(),
        );
    }

    public static function create(
        CovenantRequestDto $covenantRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): Covenant {
        $covenant = new Covenant();
        $covenant->setExternalId($externalId);
        $covenant->setStatus(DossierStatus::NEW);

        self::update($covenant, $covenantRequestDto, $organisation, $department, $subject);

        return $covenant;
    }

    public static function update(
        Covenant $covenant,
        CovenantRequestDto $covenantRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Covenant {
        $covenant->setDateFrom($covenantRequestDto->dateFrom);
        $covenant->setDateTo($covenantRequestDto->dateTo);
        $covenant->setDepartments([$department]);
        $covenant->setDocumentPrefix($covenantRequestDto->prefix);
        $covenant->setDossierNr($covenantRequestDto->dossierNumber);
        $covenant->setInternalReference($covenantRequestDto->internalReference);
        $covenant->setOrganisation($organisation);
        $covenant->setParties($covenantRequestDto->parties);
        $covenant->setPreviousVersionLink($covenantRequestDto->previousVersionLink);
        $covenant->setPublicationDate($covenantRequestDto->publicationDate);
        $covenant->setSubject($subject);
        $covenant->setSummary($covenantRequestDto->summary);
        $covenant->setTitle($covenantRequestDto->title);

        return $covenant;
    }
}
