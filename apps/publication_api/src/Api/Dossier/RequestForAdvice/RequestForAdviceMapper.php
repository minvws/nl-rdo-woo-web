<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\RequestForAdvice\Uploads\Attachment\RequestForAdviceUploadAttachmentResource;
use PublicationApi\Api\Dossier\RequestForAdvice\Uploads\MainDocument\RequestForAdviceUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class RequestForAdviceMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @param array<array-key,RequestForAdvice> $requestForAdvices
     *
     * @return list<RequestForAdviceResponseDto>
     */
    public function fromEntities(array $requestForAdvices): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $requestForAdvices,
        ));
    }

    public function fromEntity(RequestForAdvice $requestForAdvice): RequestForAdviceResponseDto
    {
        $mainDocument = $requestForAdvice->getMainDocument();
        Assert::notNull($mainDocument);

        $dateFrom = $requestForAdvice->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $requestForAdvice->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new RequestForAdviceResponseDto(
            $requestForAdvice->getId(),
            $requestForAdvice->getExternalId(),
            OrganisationMapper::fromEntity($requestForAdvice->getOrganisation()),
            $requestForAdvice->getDossierNr(),
            $requestForAdvice->getTitle(),
            $requestForAdvice->getSummary(),
            SubjectMapper::fromNullableEntity($requestForAdvice->getSubject()),
            DepartmentMapper::fromEntity($department),
            $requestForAdvice->getPublicationDate(),
            $requestForAdvice->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                RequestForAdviceUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                RequestForAdviceMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($requestForAdvice, RequestForAdviceUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dateFrom,
            $requestForAdvice->getLink(),
            $requestForAdvice->getAdvisoryBodies(),
            $this->getHalLinks($requestForAdvice),
        );
    }

    public static function create(
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): RequestForAdvice {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setExternalId($externalId);
        $requestForAdvice->setStatus(DossierStatus::NEW);
        $requestForAdvice->setDocumentPrefix($documentPrefix);

        self::update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);

        return $requestForAdvice;
    }

    public static function update(
        RequestForAdvice $requestForAdvice,
        RequestForAdviceRequestDto $requestForAdviceRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): RequestForAdvice {
        $requestForAdvice->setDateFrom($requestForAdviceRequestDto->dossierDate);
        $requestForAdvice->setDepartments([$department]);
        $requestForAdvice->setDossierNr($requestForAdviceRequestDto->dossierNumber);
        $requestForAdvice->setOrganisation($organisation);
        $requestForAdvice->setPublicationDate($requestForAdviceRequestDto->publicationDate);
        $requestForAdvice->setSubject($subject);
        $requestForAdvice->setSummary($requestForAdviceRequestDto->summary);
        $requestForAdvice->setTitle($requestForAdviceRequestDto->title);
        $requestForAdvice->setLink($requestForAdviceRequestDto->link);
        $requestForAdvice->setAdvisoryBodies($requestForAdviceRequestDto->advisoryBodies);

        return $requestForAdvice;
    }

    private function getHalLinks(RequestForAdvice $requestForAdvice): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(RequestForAdviceResource::ROUTE_NAME_GET_REQUEST_FOR_ADVICE, [
                'organisationId' => $requestForAdvice->getOrganisation()->getId(),
                'dossierExternalId' => $requestForAdvice->getExternalId(),
            ])),
        );

        if ($requestForAdvice->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($requestForAdvice))));
        }

        return $linkCollection;
    }
}
