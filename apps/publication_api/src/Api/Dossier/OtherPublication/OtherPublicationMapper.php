<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class OtherPublicationMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,OtherPublication> $otherPublications
     *
     * @return list<OtherPublicationResponseDto>
     */
    public function fromEntities(array $otherPublications): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $otherPublications,
        ));
    }

    public function fromEntity(OtherPublication $otherPublication): OtherPublicationResponseDto
    {
        $mainDocument = $otherPublication->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $dateFrom = $otherPublication->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $otherPublication->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new OtherPublicationResponseDto(
            $otherPublication->getId(),
            $otherPublication->getExternalId(),
            OrganisationMapper::fromEntity($otherPublication->getOrganisation()),
            $otherPublication->getDossierNr(),
            $otherPublication->getTitle(),
            $otherPublication->getSummary(),
            $otherPublication->getSubject()?->getName(),
            DepartmentMapper::fromEntity($department),
            $otherPublication->getPublicationDate(),
            $otherPublication->getStatus(),
            $mainDocumentDto,
            $this->attachmentResponseDtoFactory->fromEntities($otherPublication->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        OtherPublicationRequestDto $otherPublicationRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): OtherPublication {
        $otherPublication = new OtherPublication();
        $otherPublication->setExternalId($externalId);
        $otherPublication->setStatus(DossierStatus::NEW);
        $otherPublication->setDocumentPrefix($documentPrefix);

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
        $otherPublication->setDossierNr($otherPublicationRequestDto->dossierNumber);
        $otherPublication->setOrganisation($organisation);
        $otherPublication->setPublicationDate($otherPublicationRequestDto->publicationDate);
        $otherPublication->setSubject($subject);
        $otherPublication->setSummary($otherPublicationRequestDto->summary);
        $otherPublication->setTitle($otherPublicationRequestDto->title);

        return $otherPublication;
    }
}
