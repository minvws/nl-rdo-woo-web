<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Attachment\WooDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\WooDecision\Uploads\MainDocument\WooDecisionUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class WooDecisionMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
        private WooDecisionDocumentResponseDtoFactory $wooDecisionDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,WooDecision> $wooDecisions
     *
     * @return list<WooDecisionResponseDto>
     */
    public function fromEntities(array $wooDecisions): array
    {
        return array_values(array_map(self::fromEntity(...), $wooDecisions));
    }

    /**
     * @throws ResourceClassNotFoundException
     */
    public function fromEntity(WooDecision $wooDecision): WooDecisionResponseDto
    {
        $mainDocument = $wooDecision->getMainDocument();
        Assert::notNull($mainDocument);

        $publicationReason = $wooDecision->getPublicationReason();
        Assert::notNull($publicationReason);

        $department = $wooDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new WooDecisionResponseDto(
            $wooDecision->getId(),
            $wooDecision->getExternalId(),
            OrganisationMapper::fromEntity($wooDecision->getOrganisation()),
            $wooDecision->getDossierNr(),
            $wooDecision->getTitle(),
            $wooDecision->getSummary(),
            SubjectMapper::fromNullableEntity($wooDecision->getSubject()),
            DepartmentMapper::fromEntity($department),
            $wooDecision->getPublicationDate(),
            $wooDecision->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                WooDecisionUploadMainDocumentResource::ROUTE_NAME_UPLOAD,
                WooDecisionMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($wooDecision, WooDecisionUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $wooDecision->getDateFrom(),
            $wooDecision->getDateTo(),
            $wooDecision->getDecision(),
            $publicationReason,
            $wooDecision->getPreviewDate(),
            $this->wooDecisionDocumentResponseDtoFactory->fromWooDecision($wooDecision),
            $this->getHalLinks($wooDecision),
        );
    }

    public static function create(
        WooDecisionRequestDto $wooDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): WooDecision {
        $wooDecision = new WooDecision();
        $wooDecision->setExternalId($externalId);
        $wooDecision->setStatus(DossierStatus::NEW);
        $wooDecision->setDocumentPrefix($documentPrefix);

        return self::update($wooDecision, $wooDecisionRequestDto, $organisation, $department, $subject);
    }

    public static function update(
        WooDecision $wooDecision,
        WooDecisionRequestDto $wooDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): WooDecision {
        $wooDecision->setDateFrom($wooDecisionRequestDto->dateFrom);
        if ($wooDecisionRequestDto->dateTo !== null) {
            $wooDecision->setDateTo($wooDecisionRequestDto->dateTo);
        }
        $wooDecision->setDecision($wooDecisionRequestDto->decision);
        $wooDecision->setDepartments([$department]);
        $wooDecision->setDossierNr($wooDecisionRequestDto->dossierNumber);
        $wooDecision->setOrganisation($organisation);
        $wooDecision->setPreviewDate($wooDecisionRequestDto->previewDate);
        $wooDecision->setPublicationDate($wooDecisionRequestDto->publicationDate);
        $wooDecision->setPublicationReason($wooDecisionRequestDto->reason);
        $wooDecision->setSubject($subject);
        $wooDecision->setSummary($wooDecisionRequestDto->summary);
        $wooDecision->setTitle($wooDecisionRequestDto->title);

        return $wooDecision;
    }

    private function getHalLinks(WooDecision $wooDecision): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(WooDecisionResource::ROUTE_NAME_GET_WOO_DECISION, [
                'organisationId' => $wooDecision->getOrganisation()->getId(),
                'dossierExternalId' => $wooDecision->getExternalId(),
            ])),
        );

        if ($wooDecision->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($wooDecision))));
        }

        return $linkCollection;
    }
}
