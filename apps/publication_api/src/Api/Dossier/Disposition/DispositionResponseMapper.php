<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\Disposition\Uploads\Attachment\DispositionUploadAttachmentResource;
use PublicationApi\Api\Dossier\Disposition\Uploads\MainDocument\DispositionUploadMainDocumentResource;
use PublicationApi\Api\Dossier\DossierResponseMapper;
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
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<Disposition,DispositionResponseDto>
 */
readonly class DispositionResponseMapper implements DossierResponseMapper
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
     * @param array<array-key,Disposition> $dossiers
     *
     * @return list<DispositionResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): DispositionResponseDto
    {
        Assert::isInstanceOf($dossier, Disposition::class);

        return new DispositionResponseDto(
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
            $this->attachmentResponseDtoFactory->fromDossier($dossier, DispositionUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            self::dossierDate($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(Disposition $disposition): DepartmentResponseDto
    {
        $department = $disposition->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(Disposition $disposition): ?DispositionMainDocumentResponseDto
    {
        $mainDocument = $disposition->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            DispositionUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            DispositionMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(Disposition $disposition): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $disposition->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function dossierDate(Disposition $disposition): PlainDate
    {
        $dateFrom = $disposition->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(Disposition $disposition): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(DispositionResource::ROUTE_NAME_GET_DISPOSITION, [
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
