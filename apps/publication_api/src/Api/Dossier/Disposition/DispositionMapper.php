<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class DispositionMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,Disposition> $dispositions
     *
     * @return list<DispositionResponseDto>
     */
    public function fromEntities(array $dispositions): array
    {
        return array_values(array_map($this->fromEntity(...), $dispositions));
    }

    public function fromEntity(Disposition $disposition): DispositionResponseDto
    {
        $mainDocument = $disposition->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $dateFrom = $disposition->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $disposition->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new DispositionResponseDto(
            $disposition->getId(),
            $disposition->getExternalId(),
            OrganisationMapper::fromEntity($disposition->getOrganisation()),
            $disposition->getDossierNr(),
            $disposition->getTitle(),
            $disposition->getSummary(),
            $disposition->getSubject()?->getName(),
            DepartmentMapper::fromEntity($department),
            $disposition->getPublicationDate(),
            $disposition->getStatus(),
            $mainDocumentDto,
            $this->attachmentResponseDtoFactory->fromEntities($disposition->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        DispositionRequestDto $dispositionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): Disposition {
        $disposition = new Disposition();
        $disposition->setExternalId($externalId);
        $disposition->setStatus(DossierStatus::NEW);
        $disposition->setDocumentPrefix($documentPrefix);

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
        $disposition->setDossierNr($dispositionRequestDto->dossierNumber);
        $disposition->setOrganisation($organisation);
        $disposition->setPublicationDate($dispositionRequestDto->publicationDate);
        $disposition->setSubject($subject);
        $disposition->setSummary($dispositionRequestDto->summary);
        $disposition->setTitle($dispositionRequestDto->title);

        return $disposition;
    }
}
