<?php

declare(strict_types=1);

namespace PublicationApi\Api\Attachment;

use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\AttachmentUploadStatusService;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\Url;

use function array_map;
use function array_values;

readonly class AttachmentResponseDtoFactory
{
    public function __construct(
        private AttachmentUploadStatusService $attachmentUploadStatusService,
        private DossierPathHelper $dossierPathHelper,
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @return list<AttachmentResponseDto>
     */
    public function fromDossier(AbstractDossier&EntityWithAttachments $dossier, string $routeNameUpload): array
    {
        return array_values(array_map(
            function (AbstractAttachment $attachment) use ($dossier, $routeNameUpload): AttachmentResponseDto {
                return $this->fromEntity($attachment, $dossier, $routeNameUpload);
            },
            $dossier->getAttachments()->toArray(),
        ));
    }

    private function fromEntity(AbstractAttachment $attachment, AbstractDossier $dossier, string $routeNameUpload): AttachmentResponseDto
    {
        return new AttachmentResponseDto(
            $attachment->getId(),
            $attachment->getType(),
            $attachment->getLanguage(),
            $attachment->getFormalDate(),
            $attachment->getGrounds(),
            $attachment->getFileInfo()->getName(),
            $attachment->getExternalId(),
            $this->attachmentUploadStatusService->getUploadStatus($attachment),
            $this->buildLinks($attachment, $dossier, $routeNameUpload),
        );
    }

    private function buildLinks(AbstractAttachment $attachment, AbstractDossier $dossier, string $routeNameUpload): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::UPLOAD,
            new Link($this->publicUrlGenerator->buildUrlFromRoute($routeNameUpload, [
                'organisationId' => $dossier->getOrganisation()->getId(),
                'dossierExternalId' => $dossier->getExternalId(),
                'attachmentExternalId' => $attachment->getExternalId(),
            ])),
        );

        if ($dossier->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($dossier))));
            $linkCollection->set(
                LinkCollection::FILE,
                new Link($this->publicUrlGenerator->buildUrlFromRoute(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossierId' => $dossier->getDossierNr(),
                    'type' => DossierFileType::ATTACHMENT->value,
                    'id' => $attachment->getId(),
                ])),
            );
        }

        return $linkCollection;
    }
}
