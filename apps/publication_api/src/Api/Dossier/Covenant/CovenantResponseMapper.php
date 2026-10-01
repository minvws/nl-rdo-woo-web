<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\Covenant\Uploads\Attachment\CovenantUploadAttachmentResource;
use PublicationApi\Api\Dossier\Covenant\Uploads\MainDocument\CovenantUploadMainDocumentResource;
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
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<Covenant,CovenantResponseDto>
 */
readonly class CovenantResponseMapper implements DossierResponseMapper
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
     * @param array<array-key,Covenant> $dossiers
     *
     * @return list<CovenantResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): CovenantResponseDto
    {
        Assert::isInstanceOf($dossier, Covenant::class);

        return new CovenantResponseDto(
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
            $this->attachmentResponseDtoFactory->fromDossier($dossier, CovenantUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            self::dateFrom($dossier),
            $dossier->getDateTo(),
            $dossier->getPreviousVersionLink(),
            $dossier->getParties(),
            $this->getHalLinks($dossier),
        );
    }

    private function department(Covenant $covenant): DepartmentResponseDto
    {
        $department = $covenant->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(Covenant $covenant): ?CovenantMainDocumentResponseDto
    {
        $mainDocument = $covenant->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            CovenantUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            CovenantMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(Covenant $covenant): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $covenant->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function dateFrom(Covenant $covenant): PlainDate
    {
        $dateFrom = $covenant->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(Covenant $covenant): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(CovenantResource::ROUTE_NAME_GET_COVENANT, [
                'organisationId' => $covenant->getOrganisation()->getId(),
                'dossierExternalId' => $covenant->getExternalId(),
            ])),
        );

        if ($covenant->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($covenant))));
        }

        return $linkCollection;
    }
}
