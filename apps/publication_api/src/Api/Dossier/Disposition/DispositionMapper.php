<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\Disposition\Uploads\Attachment\DispositionUploadAttachmentResource;
use PublicationApi\Api\Dossier\Disposition\Uploads\MainDocument\DispositionUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class DispositionMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
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
            SubjectMapper::fromNullableEntity($disposition->getSubject()),
            DepartmentMapper::fromEntity($department),
            $disposition->getPublicationDate(),
            $disposition->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                DispositionUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                DispositionMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($disposition, DispositionUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dateFrom,
            $this->getHalLinks($disposition),
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

    private function getHalLinks(Disposition $disposition): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(DispositionResource::ROUTE_NAME_GET_DISPOSITION, [
                'organisationId' => $disposition->getOrganisation()->getId(),
                'dossierExternalId' => $disposition->getExternalId(),
            ])),
        );

        if ($disposition->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($disposition))));
        }

        return $linkCollection;
    }
}
