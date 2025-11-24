<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

use Shared\Api\Publication\V1\Attachment\AttachmentResponseDto;
use Shared\Api\Publication\V1\Department\DepartmentReferenceDto;
use Shared\Api\Publication\V1\MainDocument\MainDocumentResponseDto;
use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class OtherPublicationMapper
{
    /**
     * @param array<array-key,OtherPublication> $otherPublications
     *
     * @return array<array-key,OtherPublicationDto>
     */
    public static function fromEntities(array $otherPublications): array
    {
        return array_map(
            fn (OtherPublication $otherPublication): OtherPublicationDto => self::fromEntity($otherPublication),
            $otherPublications,
        );
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
        OtherPublicationCreateRequestDto $otherPublicationCreateRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): OtherPublication {
        $otherPublication = new OtherPublication();
        $otherPublication->setDateFrom($otherPublicationCreateRequestDto->dossierDate);
        $otherPublication->setDepartments([$department]);
        $otherPublication->setDocumentPrefix($otherPublicationCreateRequestDto->prefix);
        $otherPublication->setDossierNr($otherPublicationCreateRequestDto->dossierNumber);
        $otherPublication->setOrganisation($organisation);
        $otherPublication->setPublicationDate($otherPublicationCreateRequestDto->publicationDate);
        $otherPublication->setTitle($otherPublicationCreateRequestDto->title);
        $otherPublication->setSummary($otherPublicationCreateRequestDto->summary);
        $otherPublication->setInternalReference($otherPublicationCreateRequestDto->internalReference);
        $otherPublication->setSubject($subject);
        $otherPublication->setStatus(DossierStatus::NEW);

        return $otherPublication;
    }

    public static function update(
        OtherPublication $otherPublication,
        OtherPublicationUpdateRequestDto $otherPublicationUpdateRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): OtherPublication {
        $otherPublication->setDateFrom($otherPublicationUpdateRequestDto->dossierDate);
        $otherPublication->setDepartments([$department]);
        $otherPublication->setDocumentPrefix($otherPublicationUpdateRequestDto->prefix);
        $otherPublication->setDossierNr($otherPublicationUpdateRequestDto->dossierNumber);
        $otherPublication->setOrganisation($organisation);
        $otherPublication->setTitle($otherPublicationUpdateRequestDto->title);
        $otherPublication->setSummary($otherPublicationUpdateRequestDto->summary);
        $otherPublication->setInternalReference($otherPublicationUpdateRequestDto->internalReference);
        $otherPublication->setSubject($subject);

        return $otherPublication;
    }
}
