<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class CovenantMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,Covenant> $covenants
     *
     * @return list<CovenantResponseDto>
     */
    public function fromEntities(array $covenants): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $covenants,
        ));
    }

    public function fromEntity(Covenant $covenant): CovenantResponseDto
    {
        $mainDocument = $covenant->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $dateFrom = $covenant->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $covenant->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new CovenantResponseDto(
            $covenant->getId(),
            $covenant->getExternalId(),
            OrganisationReferenceDto::fromEntity($covenant->getOrganisation()),
            $covenant->getDossierNr(),
            $covenant->getTitle(),
            $covenant->getSummary(),
            $covenant->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $covenant->getPublicationDate(),
            $covenant->getStatus(),
            $mainDocumentDto,
            $this->attachmentResponseDtoFactory->fromEntities($covenant->getAttachments()->toArray()),
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
        ExternalId $externalId,
        string $documentPrefix,
    ): Covenant {
        $covenant = new Covenant();
        $covenant->setExternalId($externalId);
        $covenant->setStatus(DossierStatus::NEW);
        $covenant->setDocumentPrefix($documentPrefix);

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
        $covenant->setDossierNr($covenantRequestDto->dossierNumber);
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
