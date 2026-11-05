<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\ViewModel;

use Shared\ApplicationId;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierUrlGenerator;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\ViewModel\GroundViewFactory;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class MainDocumentViewFactory
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private GroundViewFactory $groundViewFactory,
        private DossierUrlGenerator $dossierUrlGenerator,
    ) {
    }

    public function make(
        AbstractDossier&EntityWithMainDocument $dossier,
        AbstractMainDocument $mainDocument,
        ApplicationId $applicationId = ApplicationId::PUBLIC,
    ): MainDocument {
        $downloadRouteName = $applicationId->isAdmin()
            ? 'app_admin_dossier_file_download'
            : 'app_dossier_file_download';

        $downloadRouteParameters = [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
            'type' => DossierFileType::MAIN_DOCUMENT->value,
            'id' => $mainDocument->getId(),
        ];

        $fileInfo = $mainDocument->getFileInfo();
        $fileSize = $fileInfo->getSize();

        return new MainDocument(
            id: $mainDocument->getId()->toRfc4122(),
            name: $fileInfo->getName(),
            formalDate: $mainDocument->getFormalDate()->format('Y-m-d'),
            type: $mainDocument->getType(),
            mimeType: $fileInfo->getMimetype(),
            sourceType: $fileInfo->getSourceType(),
            size: $fileSize,
            internalReference: $mainDocument->getInternalReference(),
            language: $mainDocument->getLanguage(),
            grounds: $this->groundViewFactory->makeCollection(Citation::sortWooCitations($mainDocument->getGrounds())),
            downloadUrl: $this->urlGenerator->generate($downloadRouteName, $downloadRouteParameters),
            detailsUrl: $this->dossierUrlGenerator->documentDetailPath($dossier),
            pageCount: $fileInfo->getPageCount() ?? 0,
            isDownloadable: $fileInfo->isUploaded() && $fileSize > 0,
        );
    }
}
