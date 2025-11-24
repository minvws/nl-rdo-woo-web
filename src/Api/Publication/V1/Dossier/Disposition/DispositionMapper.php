<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\Disposition;

use Shared\Api\Publication\V1\Attachment\AttachmentResponseDto;
use Shared\Api\Publication\V1\Department\DepartmentReferenceDto;
use Shared\Api\Publication\V1\MainDocument\MainDocumentResponseDto;
use Shared\Api\Publication\V1\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class DispositionMapper
{
    /**
     * @param array<array-key,Disposition> $dispositions
     *
     * @return array<array-key,DispositionDto>
     */
    public static function fromEntities(array $dispositions): array
    {
        return array_map(fn (Disposition $disposition): DispositionDto => self::fromEntity($disposition), $dispositions);
    }

    public static function fromEntity(Disposition $disposition): DispositionDto
    {
        $mainDocument = $disposition->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $disposition->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $disposition->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new DispositionDto(
            $disposition->getId(),
            OrganisationReferenceDto::fromEntity($disposition->getOrganisation()),
            $disposition->getDocumentPrefix(),
            $disposition->getDossierNr(),
            $disposition->getInternalReference(),
            $disposition->getTitle(),
            $disposition->getSummary(),
            $disposition->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $disposition->getPublicationDate(),
            $disposition->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($disposition->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        DispositionCreateRequestDto $dispositionCreateRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Disposition {
        $disposition = new Disposition();
        $disposition->setDateFrom($dispositionCreateRequestDto->dossierDate);
        $disposition->setDepartments([$department]);
        $disposition->setDocumentPrefix($dispositionCreateRequestDto->prefix);
        $disposition->setDossierNr($dispositionCreateRequestDto->dossierNumber);
        $disposition->setOrganisation($organisation);
        $disposition->setPublicationDate($dispositionCreateRequestDto->publicationDate);
        $disposition->setTitle($dispositionCreateRequestDto->title);
        $disposition->setSummary($dispositionCreateRequestDto->summary);
        $disposition->setInternalReference($dispositionCreateRequestDto->internalReference);
        $disposition->setSubject($subject);
        $disposition->setStatus(DossierStatus::NEW);

        return $disposition;
    }

    public static function update(
        Disposition $disposition,
        DispositionUpdateRequestDto $dispositionUpdateRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Disposition {
        $disposition->setDateFrom($dispositionUpdateRequestDto->dossierDate);
        $disposition->setDepartments([$department]);
        $disposition->setDocumentPrefix($dispositionUpdateRequestDto->prefix);
        $disposition->setDossierNr($dispositionUpdateRequestDto->dossierNumber);
        $disposition->setOrganisation($organisation);
        $disposition->setTitle($dispositionUpdateRequestDto->title);
        $disposition->setSummary($dispositionUpdateRequestDto->summary);
        $disposition->setInternalReference($dispositionUpdateRequestDto->internalReference);
        $disposition->setSubject($subject);

        return $disposition;
    }
}
