<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\ViewModel;

use Shared\ApplicationId;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use function sprintf;

readonly class AttachmentViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array<array-key,Attachment>
     */
    public function makeCollection(
        AbstractDossier $dossier,
        ApplicationId $applicationId = ApplicationId::PUBLIC,
    ): array {
        if (! $dossier instanceof EntityWithAttachments) {
            return [];
        }

        return $dossier
            ->getAttachments()
            ->filter(static fn (AbstractAttachment $entity) => ! $entity->isWithdrawn())
            ->map(fn (AbstractAttachment $entity): Attachment => $this->make($dossier, $entity, $applicationId))
            ->toArray();
    }

    public function make(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationId $applicationId = ApplicationId::PUBLIC,
    ): Attachment {
        $detailsUrl = $this->urlGenerator->generate(
            sprintf('app_%s_attachment_detail', $dossier->getType()->getValueForRouteName()),
            [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
                'attachmentId' => $attachment->getId(),
            ],
        );

        $downloadRouteName = $applicationId->isAdmin()
            ? 'app_admin_dossier_file_download'
            : 'app_dossier_file_download';

        $downloadRouteParameters = [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
            'type' => DossierFileType::ATTACHMENT->value,
            'id' => $attachment->getId(),
        ];

        $fileInfo = $attachment->getFileInfo();
        $fileSize = $fileInfo->getSize();

        return new Attachment(
            id: $attachment->getId()->toRfc4122(),
            name: $fileInfo->getName(),
            formalDate: $attachment->getFormalDate()->format('Y-m-d'),
            type: $attachment->getType(),
            mimeType: $fileInfo->getMimetype(),
            sourceType: $fileInfo->getSourceType(),
            size: $fileSize,
            internalReference: $attachment->getInternalReference(),
            language: $attachment->getLanguage(),
            grounds: Citation::sortWooCitations($attachment->getGrounds()),
            downloadUrl: $this->urlGenerator->generate($downloadRouteName, $downloadRouteParameters),
            detailsUrl: $detailsUrl,
            pageCount: $fileInfo->getPageCount() ?? 0,
            isDownloadable: $fileInfo->isUploaded() && $fileSize > 0,
            withdrawn: $attachment->isWithdrawn(),
            withdrawReason: $attachment->getWithdrawReason(),
            withdrawDate: $attachment->getWithdrawDate(),
        );
    }
}
