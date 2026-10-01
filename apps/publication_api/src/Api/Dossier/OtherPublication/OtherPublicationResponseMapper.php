<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\DossierResponseMapper;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\Attachment\OtherPublicationUploadAttachmentResource;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument\OtherPublicationUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDto;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<OtherPublication,OtherPublicationResponseDto>
 */
readonly class OtherPublicationResponseMapper implements DossierResponseMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private NoticeNotPublicResponseDtoFactory $noticeNotPublicResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,OtherPublication> $dossiers
     *
     * @return list<OtherPublicationResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): OtherPublicationResponseDto
    {
        Assert::isInstanceOf($dossier, OtherPublication::class);

        return new OtherPublicationResponseDto(
            $dossier->getId(),
            $dossier->getExternalId(),
            OrganisationMapper::fromEntity($dossier->getOrganisation()),
            $dossier->getDossierNumber(),
            $dossier->getTitle(),
            $dossier->getSummary(),
            SubjectMapper::fromNullableEntity($dossier->getSubject()),
            $this->department($dossier),
            $dossier->getPublicationDate(),
            $dossier->getStatus(),
            $this->mainDocument($dossier),
            $this->noticeNotPublic($dossier),
            $this->attachmentResponseDtoFactory->fromDossier($dossier, OtherPublicationUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            self::dossierDate($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(OtherPublication $otherPublication): DepartmentResponseDto
    {
        $department = $otherPublication->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(OtherPublication $otherPublication): ?OtherPublicationMainDocumentResponseDto
    {
        $mainDocument = $otherPublication->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            OtherPublicationUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            OtherPublicationMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(OtherPublication $otherPublication): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $otherPublication->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function dossierDate(OtherPublication $otherPublication): PlainDate
    {
        $dateFrom = $otherPublication->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(OtherPublication $otherPublication): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(OtherPublicationResource::ROUTE_NAME_GET_OTHER_PUBLICATION, [
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
