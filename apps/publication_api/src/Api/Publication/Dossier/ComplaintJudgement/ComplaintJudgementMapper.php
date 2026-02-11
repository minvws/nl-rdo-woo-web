<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class ComplaintJudgementMapper
{
    /**
     * @param array<array-key,ComplaintJudgement> $complaintJudgements
     *
     * @return list<ComplaintJudgementDto>
     */
    public static function fromEntities(array $complaintJudgements): array
    {
        return array_values(array_map(
            self::fromEntity(...),
            $complaintJudgements
        ));
    }

    public static function fromEntity(ComplaintJudgement $complaintJudgement): ComplaintJudgementDto
    {
        $mainDocument = $complaintJudgement->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $complaintJudgement->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $complaintJudgement->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new ComplaintJudgementDto(
            $complaintJudgement->getId(),
            $complaintJudgement->getExternalId(),
            OrganisationReferenceDto::fromEntity($complaintJudgement->getOrganisation()),
            $complaintJudgement->getDocumentPrefix(),
            $complaintJudgement->getDossierNr(),
            $complaintJudgement->getInternalReference(),
            $complaintJudgement->getTitle(),
            $complaintJudgement->getSummary(),
            $complaintJudgement->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $complaintJudgement->getPublicationDate(),
            $complaintJudgement->getStatus(),
            $mainDocumentDto,
            $dateFrom,
        );
    }

    public static function create(
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): ComplaintJudgement {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setExternalId($externalId);
        $complaintJudgement->setStatus(DossierStatus::NEW);

        self::update($complaintJudgement, $complaintJudgementRequestDto, $organisation, $department, $subject);

        return $complaintJudgement;
    }

    public static function update(
        ComplaintJudgement $complaintJudgement,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): ComplaintJudgement {
        $complaintJudgement->setDateFrom($complaintJudgementRequestDto->dossierDate);
        $complaintJudgement->setDepartments([$department]);
        $complaintJudgement->setDocumentPrefix($complaintJudgementRequestDto->prefix);
        $complaintJudgement->setDossierNr($complaintJudgementRequestDto->dossierNumber);
        $complaintJudgement->setOrganisation($organisation);
        $complaintJudgement->setPublicationDate($complaintJudgementRequestDto->publicationDate);
        $complaintJudgement->setTitle($complaintJudgementRequestDto->title);
        $complaintJudgement->setSummary($complaintJudgementRequestDto->summary);
        $complaintJudgement->setInternalReference($complaintJudgementRequestDto->internalReference);
        $complaintJudgement->setSubject($subject);

        return $complaintJudgement;
    }
}
