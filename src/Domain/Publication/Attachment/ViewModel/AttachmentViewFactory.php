<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\ViewModel;

use App\Citation;
use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Enum\ApplicationMode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        ApplicationMode $mode = ApplicationMode::PUBLIC,
    ): array {
        if (! $dossier instanceof EntityWithAttachments) {
            return [];
        }

        return $dossier
            ->getAttachments()
            ->map(fn (AbstractAttachment $entity): Attachment => $this->make($dossier, $entity, $mode))
            ->toArray();
    }

    public function make(
        AbstractDossier&EntityWithAttachments $dossier,
        AbstractAttachment $attachment,
        ApplicationMode $mode = ApplicationMode::PUBLIC,
    ): Attachment {
        $detailsUrl = $this->urlGenerator->generate(
            sprintf('app_%s_attachment_detail', $dossier->getType()->getValueForRouteName()),
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'attachmentId' => $attachment->getId(),
            ],
        );

        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_dossier_file_download'
            : 'app_dossier_file_download';

        $downloadRouteParameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'type' => DossierFileType::ATTACHMENT->value,
            'id' => $attachment->getId(),
        ];

        return new Attachment(
            id: $attachment->getId()->toRfc4122(),
            name: $attachment->getFileInfo()->getName(),
            formalDate: $attachment->getFormalDate()->format('Y-m-d'),
            type: $attachment->getType(),
            mimeType: $attachment->getFileInfo()->getMimetype(),
            sourceType: $attachment->getFileInfo()->getSourceType(),
            size: $attachment->getFileInfo()->getSize(),
            internalReference: $attachment->getInternalReference(),
            language: $attachment->getLanguage(),
            grounds: Citation::sortWooCitations($attachment->getGrounds()),
            downloadUrl: $this->urlGenerator->generate($downloadRouteName, $downloadRouteParameters),
            detailsUrl: $detailsUrl,
            pageCount: $attachment->getFileInfo()->getPageCount() ?? 0,
        );
    }
}
