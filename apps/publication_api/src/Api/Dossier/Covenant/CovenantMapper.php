<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\Covenant\Uploads\Attachment\CovenantUploadAttachmentResource;
use PublicationApi\Api\Dossier\Covenant\Uploads\MainDocument\CovenantUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class CovenantMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @param array<array-key,Covenant> $covenants
     *
     * @return list<CovenantResponseDto>
     */
    public function fromEntities(array $covenants): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $covenants,
        ));
    }

    public function fromEntity(Covenant $covenant): CovenantResponseDto
    {
        $mainDocument = $covenant->getMainDocument();
        Assert::notNull($mainDocument);

        $dateFrom = $covenant->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $covenant->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new CovenantResponseDto(
            $covenant->getId(),
            $covenant->getExternalId(),
            OrganisationMapper::fromEntity($covenant->getOrganisation()),
            $covenant->getDossierNr(),
            $covenant->getTitle(),
            $covenant->getSummary(),
            SubjectMapper::fromNullableEntity($covenant->getSubject()),
            DepartmentMapper::fromEntity($department),
            $covenant->getPublicationDate(),
            $covenant->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                CovenantUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                CovenantMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($covenant, CovenantUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dateFrom,
            $covenant->getDateTo(),
            $covenant->getPreviousVersionLink(),
            $covenant->getParties(),
            $this->getHalLinks($covenant),
        );
    }

    public static function create(
        CovenantRequestDto $covenantRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): Covenant {
        $covenant = new Covenant();
        $covenant->setExternalId($externalId);
        $covenant->setStatus(DossierStatus::NEW);
        $covenant->setDocumentPrefix($documentPrefix);

        self::update($covenant, $covenantRequestDto, $organisation, $department, $subject);

        return $covenant;
    }

    public static function update(
        Covenant $covenant,
        CovenantRequestDto $covenantRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Covenant {
        $covenant->setDateFrom($covenantRequestDto->dateFrom);
        $covenant->setDateTo($covenantRequestDto->dateTo);
        $covenant->setDepartments([$department]);
        $covenant->setDossierNr($covenantRequestDto->dossierNumber);
        $covenant->setOrganisation($organisation);
        $covenant->setParties($covenantRequestDto->parties);
        $covenant->setPreviousVersionLink($covenantRequestDto->previousVersionLink);
        $covenant->setPublicationDate($covenantRequestDto->publicationDate);
        $covenant->setSubject($subject);
        $covenant->setSummary($covenantRequestDto->summary);
        $covenant->setTitle($covenantRequestDto->title);

        return $covenant;
    }

    private function getHalLinks(Covenant $covenant): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(CovenantResource::ROUTE_NAME_GET_COVENANT, [
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
