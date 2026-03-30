<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\RequestForAdvice;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class RequestForAdviceMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,RequestForAdvice> $requestForAdvices
     *
     * @return list<RequestForAdviceDto>
     */
    public function fromEntities(array $requestForAdvices): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $requestForAdvices,
        ));
    }

    public function fromEntity(RequestForAdvice $requestForAdvice): RequestForAdviceDto
    {
        $mainDocument = $requestForAdvice->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $dateFrom = $requestForAdvice->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $requestForAdvice->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new RequestForAdviceDto(
            $requestForAdvice->getId(),
            $requestForAdvice->getExternalId(),
            OrganisationReferenceDto::fromEntity($requestForAdvice->getOrganisation()),
            $requestForAdvice->getDocumentPrefix(),
            $requestForAdvice->getDossierNr(),
            $requestForAdvice->getInternalReference(),
            $requestForAdvice->getTitle(),
            $requestForAdvice->getSummary(),
            $requestForAdvice->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $requestForAdvice->getPublicationDate(),
            $requestForAdvice->getStatus(),
            $mainDocumentDto,
            $this->attachmentResponseDtoFactory->fromEntities($requestForAdvice->getAttachments()->toArray()),
            $dateFrom,
            $requestForAdvice->getLink(),
            $requestForAdvice->getAdvisoryBodies(),
        );
    }

    public static function create(
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
    ): RequestForAdvice {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setExternalId($externalId);
        $requestForAdvice->setStatus(DossierStatus::NEW);

        self::update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);

        return $requestForAdvice;
    }

    public static function update(
        RequestForAdvice $requestForAdvice,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): RequestForAdvice {
        $requestForAdvice->setDateFrom($requestForAdviceRequestDto->dossierDate);
        $requestForAdvice->setDepartments([$department]);
        $requestForAdvice->setDocumentPrefix($requestForAdviceRequestDto->prefix);
        $requestForAdvice->setDossierNr($requestForAdviceRequestDto->dossierNumber);
        $requestForAdvice->setInternalReference($requestForAdviceRequestDto->internalReference);
        $requestForAdvice->setOrganisation($organisation);
        $requestForAdvice->setPublicationDate($requestForAdviceRequestDto->publicationDate);
        $requestForAdvice->setSubject($subject);
        $requestForAdvice->setSummary($requestForAdviceRequestDto->summary);
        $requestForAdvice->setTitle($requestForAdviceRequestDto->title);
        $requestForAdvice->setLink($requestForAdviceRequestDto->link);
        $requestForAdvice->setAdvisoryBodies($requestForAdviceRequestDto->advisoryBodies);

        return $requestForAdvice;
    }
}
