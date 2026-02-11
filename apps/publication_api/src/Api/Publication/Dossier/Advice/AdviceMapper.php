<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Advice;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class AdviceMapper
{
    /**
     * @param array<array-key,Advice> $advices
     *
     * @return list<AdviceDto>
     */
    public static function fromEntities(array $advices): array
    {
        return array_values(array_map(
            self::fromEntity(...),
            $advices,
        ));
    }

    public static function fromEntity(Advice $advice): AdviceDto
    {
        $mainDocument = $advice->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $advice->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $advice->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new AdviceDto(
            $advice->getId(),
            $advice->getExternalId(),
            OrganisationReferenceDto::fromEntity($advice->getOrganisation()),
            $advice->getDocumentPrefix(),
            $advice->getDossierNr(),
            $advice->getInternalReference(),
            $advice->getTitle(),
            $advice->getSummary(),
            $advice->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $advice->getPublicationDate(),
            $advice->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($advice->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        AdviceRequestDto $adviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): Advice {
        $advice = new Advice();
        $advice->setExternalId($externalId);
        $advice->setStatus(DossierStatus::NEW);

        self::update($advice, $adviceRequestDto, $organisation, $department, $subject);

        return $advice;
    }

    public static function update(
        Advice $advice,
        AdviceRequestDto $adviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Advice {
        $advice->setDateFrom($adviceRequestDto->dossierDate);
        $advice->setDepartments([$department]);
        $advice->setDocumentPrefix($adviceRequestDto->prefix);
        $advice->setDossierNr($adviceRequestDto->dossierNumber);
        $advice->setInternalReference($adviceRequestDto->internalReference);
        $advice->setOrganisation($organisation);
        $advice->setPublicationDate($adviceRequestDto->publicationDate);
        $advice->setSubject($subject);
        $advice->setSummary($adviceRequestDto->summary);
        $advice->setTitle($adviceRequestDto->title);

        return $advice;
    }
}
