<?php

declare(strict_types=1);

namespace PublicationApi\Api\MainDocument;

use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\MainDocumentUploadStatusService;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\Url;

readonly class MainDocumentResponseDtoFactory
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentUploadStatusService $mainDocumentUploadStatusService,
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @template T of MainDocumentResponseDtoInterface
     *
     * @param class-string<T> $responseDtoClass
     *
     * @return T
     */
    public function fromEntity(
        AbstractMainDocument $mainDocument,
        string $routeNameUpload,
        string $responseDtoClass,
    ): MainDocumentResponseDtoInterface {
        return new $responseDtoClass(
            $mainDocument->getId(),
            $mainDocument->getType(),
            $mainDocument->getLanguage(),
            $mainDocument->getFormalDate(),
            $mainDocument->getGrounds(),
            $mainDocument->getFileInfo()->getName(),
            $this->mainDocumentUploadStatusService->getUploadStatus($mainDocument),
            $this->getHalLinks($mainDocument, $routeNameUpload),
        );
    }

    /**
     * For dossier types whose documents may not be redacted, so their response DTO has no grounds property.
     *
     * @template T of MainDocumentResponseDtoInterface
     *
     * @param class-string<T> $responseDtoClass
     *
     * @return T
     */
    public function fromEntityWithoutGrounds(
        AbstractMainDocument $mainDocument,
        string $routeNameUpload,
        string $responseDtoClass,
    ): MainDocumentResponseDtoInterface {
        return new $responseDtoClass(
            $mainDocument->getId(),
            $mainDocument->getType(),
            $mainDocument->getLanguage(),
            $mainDocument->getFormalDate(),
            $mainDocument->getFileInfo()->getName(),
            $this->mainDocumentUploadStatusService->getUploadStatus($mainDocument),
            $this->getHalLinks($mainDocument, $routeNameUpload),
        );
    }

    private function getHalLinks(AbstractMainDocument $mainDocument, string $routeNameUpload): LinkCollection
    {
        $dossier = $mainDocument->getDossier();
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::UPLOAD,
            new Link($this->apiUrlGenerator->buildUrlFromRoute($routeNameUpload, [
                'organisationId' => $dossier->getOrganisation()->getId(),
                'dossierExternalId' => $dossier->getExternalId(),
            ])),
        );

        if ($dossier->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($dossier))));
            $linkCollection->set(
                LinkCollection::FILE,
                new Link($this->publicUrlGenerator->buildUrlFromRoute(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
                    'documentPrefix' => $dossier->getDocumentPrefix(),
                    'dossierNumber' => $dossier->getDossierNumber(),
                    'type' => DossierFileType::MAIN_DOCUMENT->value,
                    'id' => $mainDocument->getId(),
                ])),
            );
        }

        return $linkCollection;
    }
}
