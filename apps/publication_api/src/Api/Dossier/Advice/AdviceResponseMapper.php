<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\Advice\Uploads\Attachment\AdviceUploadAttachmentResource;
use PublicationApi\Api\Dossier\Advice\Uploads\MainDocument\AdviceUploadMainDocumentResource;
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
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<Advice,AdviceResponseDto>
 */
readonly class AdviceResponseMapper implements DossierResponseMapper
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
     * @param array<array-key,Advice> $dossiers
     *
     * @return list<AdviceResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): AdviceResponseDto
    {
        Assert::isInstanceOf($dossier, Advice::class);

        return new AdviceResponseDto(
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
            $this->attachmentResponseDtoFactory->fromDossier($dossier, AdviceUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            self::dossierDate($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(Advice $advice): DepartmentResponseDto
    {
        $department = $advice->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(Advice $advice): ?AdviceMainDocumentResponseDto
    {
        $mainDocument = $advice->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            AdviceUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            AdviceMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(Advice $advice): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $advice->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function dossierDate(Advice $advice): PlainDate
    {
        $dateFrom = $advice->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(Advice $advice): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(AdviceResource::ROUTE_NAME_GET_ADVICE, [
                'organisationId' => $advice->getOrganisation()->getId(),
                'dossierExternalId' => $advice->getExternalId(),
            ])),
        );

        if ($advice->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($advice))));
        }

        return $linkCollection;
    }
}
