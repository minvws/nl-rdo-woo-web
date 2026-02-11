<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class OtherPublicationMapper
{
    /**
     * @param array<array-key,OtherPublication> $otherPublications
     *
     * @return list<OtherPublicationDto>
     */
    public static function fromEntities(array $otherPublications): array
    {
        return array_values(array_map(
            self::fromEntity(...),
            $otherPublications,
        ));
    }

    public static function fromEntity(OtherPublication $otherPublication): OtherPublicationDto
    {
        $mainDocument = $otherPublication->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $otherPublication->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $otherPublication->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new OtherPublicationDto(
            $otherPublication->getId(),
            $otherPublication->getExternalId(),
            OrganisationReferenceDto::fromEntity($otherPublication->getOrganisation()),
            $otherPublication->getDocumentPrefix(),
            $otherPublication->getDossierNr(),
            $otherPublication->getInternalReference(),
            $otherPublication->getTitle(),
            $otherPublication->getSummary(),
            $otherPublication->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $otherPublication->getPublicationDate(),
            $otherPublication->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($otherPublication->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        OtherPublicationRequestDto $otherPublicationRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): OtherPublication {
        $otherPublication = new OtherPublication();
        $otherPublication->setExternalId($externalId);
        $otherPublication->setStatus(DossierStatus::NEW);

        self::update($otherPublication, $otherPublicationRequestDto, $organisation, $department, $subject);

        return $otherPublication;
    }

    public static function update(
        OtherPublication $otherPublication,
        OtherPublicationRequestDto $otherPublicationRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): OtherPublication {
        $otherPublication->setDateFrom($otherPublicationRequestDto->dossierDate);
        $otherPublication->setDepartments([$department]);
        $otherPublication->setDocumentPrefix($otherPublicationRequestDto->prefix);
        $otherPublication->setDossierNr($otherPublicationRequestDto->dossierNumber);
        $otherPublication->setInternalReference($otherPublicationRequestDto->internalReference);
        $otherPublication->setOrganisation($organisation);
        $otherPublication->setPublicationDate($otherPublicationRequestDto->publicationDate);
        $otherPublication->setSubject($subject);
        $otherPublication->setSummary($otherPublicationRequestDto->summary);
        $otherPublication->setTitle($otherPublicationRequestDto->title);

        return $otherPublication;
    }
}
