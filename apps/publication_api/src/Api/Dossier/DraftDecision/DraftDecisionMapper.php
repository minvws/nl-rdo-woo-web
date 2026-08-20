<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment\DraftDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument\DraftDecisionUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class DraftDecisionMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,DraftDecision> $draftDecisions
     *
     * @return list<DraftDecisionResponseDto>
     */
    public function fromEntities(array $draftDecisions): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $draftDecisions,
        ));
    }

    public function fromEntity(DraftDecision $draftDecision): DraftDecisionResponseDto
    {
        $mainDocument = $draftDecision->getMainDocument();

        $dateFrom = $draftDecision->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $draftDecision->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        $mainDocumentDto = $mainDocument !== null
            ? $this->mainDocumentResponseDtoFactory->fromEntityWithoutGrounds(
                $mainDocument,
                DraftDecisionUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                DraftDecisionMainDocumentResponseDto::class,
            )
            : null;

        $attachmentDtos = array_map(
            DraftDecisionAttachmentResponseDto::fromAttachmentResponseDto(...),
            $this->attachmentResponseDtoFactory->fromDossier($draftDecision, DraftDecisionUploadAttachmentResource::ROUTE_NAME_UPLOAD),
        );

        return new DraftDecisionResponseDto(
            $draftDecision->getId(),
            $draftDecision->getExternalId(),
            OrganisationMapper::fromEntity($draftDecision->getOrganisation()),
            $draftDecision->getDossierNumber(),
            $draftDecision->getTitle(),
            $draftDecision->getSummary(),
            SubjectMapper::fromNullableEntity($draftDecision->getSubject()),
            DepartmentMapper::fromEntity($department),
            $draftDecision->getPublicationDate(),
            $draftDecision->getStatus(),
            $mainDocumentDto,
            $attachmentDtos,
            $dateFrom,
            $this->getHalLinks($draftDecision),
        );
    }

    public static function create(
        DraftDecisionRequestDto $draftDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): DraftDecision {
        $draftDecision = new DraftDecision();
        $draftDecision->setExternalId($externalId);
        $draftDecision->setStatus(DossierStatus::CONCEPT);
        $draftDecision->setDocumentPrefix($documentPrefix);

        self::update($draftDecision, $draftDecisionRequestDto, $organisation, $department, $subject);

        return $draftDecision;
    }

    public static function update(
        DraftDecision $draftDecision,
        DraftDecisionRequestDto $draftDecisionRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): DraftDecision {
        $draftDecision->setDateFrom($draftDecisionRequestDto->dossierDate);
        $draftDecision->setDepartments([$department]);
        $draftDecision->setDossierNumber($draftDecisionRequestDto->dossierNumber);
        $draftDecision->setOrganisation($organisation);
        if (! $draftDecision->getStatus()->isPublished()) {
            $draftDecision->setPublicationDate($draftDecisionRequestDto->publicationDate);
        }
        $draftDecision->setSubject($subject);
        $draftDecision->setSummary($draftDecisionRequestDto->summary);
        $draftDecision->setTitle($draftDecisionRequestDto->title);

        return $draftDecision;
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
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($draftDecision))));
        }

        return $linkCollection;
    }
}
