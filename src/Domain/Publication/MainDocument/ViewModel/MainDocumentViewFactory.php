<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\ViewModel;

use App\Domain\Publication\Citation;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\FileProvider\DossierFileType;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class MainDocumentViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationMode $mode = ApplicationMode::PUBLIC,
    ): MainDocument {
        $detailsUrl = $this->urlGenerator->generate(
            sprintf('app_%s_document_detail', $dossier->getType()->getValueForRouteName()),
            [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
            ],
        );

        $downloadRouteName = $mode === ApplicationMode::ADMIN
            ? 'app_admin_dossier_file_download'
            : 'app_dossier_file_download';

        $downloadRouteParameters = [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'type' => DossierFileType::MAIN_DOCUMENT->value,
            'id' => $mainDocument->getId(),
        ];

        return new MainDocument(
            id: $mainDocument->getId()->toRfc4122(),
            name: $mainDocument->getFileInfo()->getName(),
            formalDate: $mainDocument->getFormalDate()->format('Y-m-d'),
            type: $mainDocument->getType(),
            mimeType: $mainDocument->getFileInfo()->getMimetype(),
            sourceType: $mainDocument->getFileInfo()->getSourceType(),
            size: $mainDocument->getFileInfo()->getSize(),
            internalReference: $mainDocument->getInternalReference(),
            language: $mainDocument->getLanguage(),
            grounds: Citation::sortWooCitations($mainDocument->getGrounds()),
            downloadUrl: $this->urlGenerator->generate($downloadRouteName, $downloadRouteParameters),
            detailsUrl: $detailsUrl,
            pageCount: $mainDocument->getFileInfo()->getPageCount() ?? 0,
        );
    }
}
