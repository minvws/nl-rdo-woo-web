<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Service\Storage\ThumbnailStorageService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class DossierFileViewFactory
{
    public function __construct(
        private ThumbnailStorageService $thumbnailStorage,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function make(
        AbstractDossier $dossier,
        EntityWithFileInfo $entity,
        DossierFileType $type,
    ): DossierFile {
        $downloadUrl = $this->urlGenerator->generate('app_dossier_file_download', [
            'prefix' => $dossier->getDocumentPrefix(),
            'dossierId' => $dossier->getDossierNr(),
            'type' => $type->value,
            'id' => $entity->getId(),
        ]);

        if (! $entity->getFileInfo()->hasPages()) {
            return new DossierFile(
                $entity->getFileInfo()->getType() ?? '',
                $entity->getFileInfo()->getSize(),
                false,
                $downloadUrl,
            );
        }

        /** @var Page[] $pages */
        $pages = [];

        /** @var int $pageNr */
        foreach (range(1, $entity->getFileInfo()->getPageCount()) as $pageNr) {
            $pages[] = $this->makePage($entity, $pageNr, $dossier, $type, $downloadUrl);
        }

        return new DossierFile(
            $entity->getFileInfo()->getType() ?? '',
            $entity->getFileInfo()->getSize(),
            true,
            $downloadUrl,
            ...$pages,
        );
    }

    private function makePage(
        EntityWithFileInfo $entity,
        int $pageNr,
        AbstractDossier $dossier,
        DossierFileType $type,
        string $downloadUrl,
    ): Page {
        if ($this->thumbnailStorage->exists($entity, $pageNr)) {
            $thumbUrl = $this->urlGenerator->generate('app_dossier_file_thumbnail', [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'type' => $type->value,
                'id' => $entity->getId(),
                'pageNr' => $pageNr,
                'hash' => $entity->getFileInfo()->getHash(),
            ]);
            $viewUrl = $downloadUrl . '#page=' . $pageNr;
        } else {
            $thumbUrl = null;
            $viewUrl = null;
        }

        return new Page(
            $pageNr,
            $thumbUrl,
            $viewUrl,
        );
    }
}
