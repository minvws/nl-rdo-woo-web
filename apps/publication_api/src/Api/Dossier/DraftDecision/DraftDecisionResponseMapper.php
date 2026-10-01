<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\DossierResponseMapper;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment\DraftDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument\DraftDecisionUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<DraftDecision,DraftDecisionResponseDto>
 */
readonly class DraftDecisionResponseMapper implements DossierResponseMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,DraftDecision> $dossiers
     *
     * @return list<DraftDecisionResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): DraftDecisionResponseDto
    {
        Assert::isInstanceOf($dossier, DraftDecision::class);

        return new DraftDecisionResponseDto(
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
            $this->attachments($dossier),
            self::dossierDate($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(DraftDecision $draftDecision): DepartmentResponseDto
    {
        $department = $draftDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(DraftDecision $draftDecision): ?DraftDecisionMainDocumentResponseDto
    {
        $mainDocument = $draftDecision->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntityWithoutGrounds(
            $mainDocument,
            DraftDecisionUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            DraftDecisionMainDocumentResponseDto::class,
        );
    }

    /**
     * @return list<DraftDecisionAttachmentResponseDto>
     */
    private function attachments(DraftDecision $draftDecision): array
    {
        return array_values(array_map(
            DraftDecisionAttachmentResponseDto::fromAttachmentResponseDto(...),
            $this->attachmentResponseDtoFactory->fromDossier($draftDecision, DraftDecisionUploadAttachmentResource::ROUTE_NAME_UPLOAD),
        ));
    }

    private static function dossierDate(DraftDecision $draftDecision): PlainDate
    {
        $dateFrom = $draftDecision->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(DraftDecision $draftDecision): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(DraftDecisionResource::ROUTE_NAME_GET_DRAFT_DECISION, [
                'organisationId' => $draftDecision->getOrganisation()->getId(),
                'dossierExternalId' => $draftDecision->getExternalId(),
            ])),
        );

        if ($draftDecision->getStatus()->isPublished()) {
            $linkCollection->set(
                LinkCollection::PUBLIC,
                new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($draftDecision))),
            );
        }

        return $linkCollection;
    }
}
