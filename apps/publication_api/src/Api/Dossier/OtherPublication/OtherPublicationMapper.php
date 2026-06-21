<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\Attachment\OtherPublicationUploadAttachmentResource;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument\OtherPublicationUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class OtherPublicationMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
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
            SubjectMapper::fromNullableEntity($otherPublication->getSubject()),
            DepartmentMapper::fromEntity($department),
            $otherPublication->getPublicationDate(),
            $otherPublication->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                OtherPublicationUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                OtherPublicationMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($otherPublication, OtherPublicationUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dateFrom,
            $this->getHalLinks($otherPublication),
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

    private function getHalLinks(OtherPublication $otherPublication): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(OtherPublicationResource::ROUTE_NAME_GET_OTHER_PUBLICATION, [
                'organisationId' => $otherPublication->getOrganisation()->getId(),
                'dossierExternalId' => $otherPublication->getExternalId(),
            ])),
        );

        if ($otherPublication->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($otherPublication))));
        }

        return $linkCollection;
    }
}
