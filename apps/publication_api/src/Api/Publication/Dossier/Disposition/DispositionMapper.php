<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Disposition;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class DispositionMapper
{
    /**
     * @param array<array-key,Disposition> $dispositions
     *
     * @return list<DispositionDto>
     */
    public static function fromEntities(array $dispositions): array
    {
        return array_values(array_map(self::fromEntity(...), $dispositions));
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
            $disposition->getExternalId(),
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
        DispositionRequestDto $dispositionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): Disposition {
        $disposition = new Disposition();
        $disposition->setExternalId($externalId);
        $disposition->setStatus(DossierStatus::NEW);

        self::update($disposition, $dispositionRequestDto, $organisation, $department, $subject);

        return $disposition;
    }

    public static function update(
        Disposition $disposition,
        DispositionRequestDto $dispositionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Disposition {
        $disposition->setDateFrom($dispositionRequestDto->dossierDate);
        $disposition->setDepartments([$department]);
        $disposition->setDocumentPrefix($dispositionRequestDto->prefix);
        $disposition->setDossierNr($dispositionRequestDto->dossierNumber);
        $disposition->setInternalReference($dispositionRequestDto->internalReference);
        $disposition->setOrganisation($organisation);
        $disposition->setPublicationDate($dispositionRequestDto->publicationDate);
        $disposition->setSubject($subject);
        $disposition->setSummary($dispositionRequestDto->summary);
        $disposition->setTitle($dispositionRequestDto->title);

        return $disposition;
    }
}
