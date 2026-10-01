<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\DossierResponseMapper;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Inquiry\InquiryLinkFactory;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Attachment\WooDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\WooDecision\Uploads\MainDocument\WooDecisionUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<WooDecision,WooDecisionResponseDto>
 */
readonly class WooDecisionResponseMapper implements DossierResponseMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private InquiryLinkFactory $inquiryLinkFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private WooDecisionDocumentResponseDtoFactory $wooDecisionDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,WooDecision> $dossiers
     *
     * @return list<WooDecisionResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): WooDecisionResponseDto
    {
        Assert::isInstanceOf($dossier, WooDecision::class);

        return new WooDecisionResponseDto(
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
            $this->attachmentResponseDtoFactory->fromDossier($dossier, WooDecisionUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dossier->getDateFrom(),
            $dossier->getDateTo(),
            $dossier->getDecision(),
            self::publicationReason($dossier),
            $dossier->getPreviewDate(),
            $this->wooDecisionDocumentResponseDtoFactory->fromWooDecision($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(WooDecision $wooDecision): DepartmentResponseDto
    {
        $department = $wooDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(WooDecision $wooDecision): WooDecisionMainDocumentResponseDto
    {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            WooDecisionUploadMainDocumentResource::ROUTE_NAME_UPLOAD,
            WooDecisionMainDocumentResponseDto::class,
        );
    }

    private static function publicationReason(WooDecision $wooDecision): PublicationReason
    {
        $publicationReason = $wooDecision->getPublicationReason();
        Assert::notNull($publicationReason);

        return $publicationReason;
    }

    private function getHalLinks(WooDecision $wooDecision): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(WooDecisionResource::ROUTE_NAME_GET_WOO_DECISION, [
                'organisationId' => $wooDecision->getOrganisation()->getId(),
                'dossierExternalId' => $wooDecision->getExternalId(),
            ])),
        );

        if ($wooDecision->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($wooDecision))));
        }

        foreach ($wooDecision->getInquiries() as $inquiry) {
            $linkCollection->add(LinkCollection::INQUIRIES, $this->inquiryLinkFactory->fromInquiry($inquiry));
        }

        return $linkCollection;
    }
}
