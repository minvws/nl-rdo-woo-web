<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement;

use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class ComplaintJudgementMapper
{
    public function __construct(
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,ComplaintJudgement> $complaintJudgements
     *
     * @return list<ComplaintJudgementResponseDto>
     */
    public function fromEntities(array $complaintJudgements): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $complaintJudgements,
        ));
    }

    public function fromEntity(ComplaintJudgement $complaintJudgement): ComplaintJudgementResponseDto
    {
        $mainDocument = $complaintJudgement->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $dateFrom = $complaintJudgement->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $complaintJudgement->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new ComplaintJudgementResponseDto(
            $complaintJudgement->getId(),
            $complaintJudgement->getExternalId(),
            OrganisationReferenceDto::fromEntity($complaintJudgement->getOrganisation()),
            $complaintJudgement->getDossierNr(),
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
        ExternalId $externalId,
        string $documentPrefix,
    ): ComplaintJudgement {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setExternalId($externalId);
        $complaintJudgement->setStatus(DossierStatus::NEW);
        $complaintJudgement->setDocumentPrefix($documentPrefix);

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
        $complaintJudgement->setDossierNr($complaintJudgementRequestDto->dossierNumber);
        $complaintJudgement->setOrganisation($organisation);
        $complaintJudgement->setPublicationDate($complaintJudgementRequestDto->publicationDate);
        $complaintJudgement->setTitle($complaintJudgementRequestDto->title);
        $complaintJudgement->setSummary($complaintJudgementRequestDto->summary);
        $complaintJudgement->setSubject($subject);

        return $complaintJudgement;
    }
}
