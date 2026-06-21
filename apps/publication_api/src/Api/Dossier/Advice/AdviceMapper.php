<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\Advice\Uploads\Attachment\AdviceUploadAttachmentResource;
use PublicationApi\Api\Dossier\Advice\Uploads\MainDocument\AdviceUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class AdviceMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @param array<array-key,Advice> $advices
     *
     * @return list<AdviceResponseDto>
     */
    public function fromEntities(array $advices): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $advices,
        ));
    }

    public function fromEntity(Advice $advice): AdviceResponseDto
    {
        $mainDocument = $advice->getMainDocument();
        Assert::notNull($mainDocument);

        $dateFrom = $advice->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $advice->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new AdviceResponseDto(
            $advice->getId(),
            $advice->getExternalId(),
            OrganisationMapper::fromEntity($advice->getOrganisation()),
            $advice->getDossierNr(),
            $advice->getTitle(),
            $advice->getSummary(),
            SubjectMapper::fromNullableEntity($advice->getSubject()),
            DepartmentMapper::fromEntity($department),
            $advice->getPublicationDate(),
            $advice->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                AdviceUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                AdviceMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($advice, AdviceUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dateFrom,
            $this->getHalLinks($advice),
        );
    }

    public static function create(
        AdviceRequestDto $adviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): Advice {
        $advice = new Advice();
        $advice->setExternalId($externalId);
        $advice->setStatus(DossierStatus::NEW);
        $advice->setDocumentPrefix($documentPrefix);

        self::update($advice, $adviceRequestDto, $organisation, $department, $subject);

        return $advice;
    }

    public static function update(
        Advice $advice,
        AdviceRequestDto $adviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): Advice {
        $advice->setDateFrom($adviceRequestDto->dossierDate);
        $advice->setDepartments([$department]);
        $advice->setDossierNr($adviceRequestDto->dossierNumber);
        $advice->setOrganisation($organisation);
        $advice->setPublicationDate($adviceRequestDto->publicationDate);
        $advice->setSubject($subject);
        $advice->setSummary($adviceRequestDto->summary);
        $advice->setTitle($adviceRequestDto->title);

        return $advice;
    }

    private function getHalLinks(Advice $advice): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(AdviceResource::ROUTE_NAME_GET_ADVICE, [
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
