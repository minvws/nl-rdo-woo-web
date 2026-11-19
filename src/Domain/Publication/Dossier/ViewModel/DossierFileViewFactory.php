<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Controller\Public\Dossier\WooDecision\DocumentController;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\EntityWithSpreadsheetTable;
use Shared\Domain\Publication\MainDocument\SpreadsheetTable\SpreadsheetTable;
use Shared\Service\Storage\ThumbnailStorageService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

use function array_map;
use function range;

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
        $downloadUrl = $this->getDownloadUrl($entity, $dossier, $type);
        $fileInfo = $entity->getFileInfo();
        $fileSize = $fileInfo->getSize();
        $fileType = $fileInfo->getType() ?? '';

        $spreadsheetPreview = $this->makeSpreadsheetPreview($entity);
        if ($spreadsheetPreview instanceof SpreadsheetPreview) {
            return new DossierFile($fileType, $fileSize, false, $downloadUrl, $spreadsheetPreview);
        }

        if ($fileInfo->hasPages()) {
            $pages = $this->makePages($entity, $dossier, $type, $downloadUrl, $fileInfo->getHash(), $fileInfo->getPageCount());

            return new DossierFile($fileType, $fileSize, true, $downloadUrl, null, ...$pages);
        }

        return new DossierFile($fileType, $fileSize, false, $downloadUrl, null);
    }

    /**
     * @return list<Page>
     */
    private function makePages(
        EntityWithFileInfo $entity,
        AbstractDossier $dossier,
        DossierFileType $type,
        string $downloadUrl,
        ?string $hash,
        int $pageCount,
    ): array {
        $documentPrefix = $dossier->getDocumentPrefix();
        $dossierNumber = $dossier->getDossierNumber();
        $id = $entity->getId();

        return array_map(
            fn (int $pageNr) => $this->makePage(
                entity: $entity,
                pageNr: $pageNr,
                documentPrefix: $documentPrefix,
                dossierNumber: $dossierNumber,
                type: $type,
                id: $id,
                hash: $hash,
                downloadUrl: $downloadUrl,
            ),
            range(1, $pageCount),
        );
    }

    private function makePage(
        EntityWithFileInfo $entity,
        int $pageNr,
        string $documentPrefix,
        string $dossierNumber,
        DossierFileType $type,
        Uuid $id,
        ?string $hash,
        string $downloadUrl,
    ): Page {
        if ($this->thumbnailStorage->exists($entity, $pageNr)) {
            $thumbUrl = $this->urlGenerator->generate(
                DossierFileController::ROUTE_NAME_DOSSIER_FILE_THUMBNAIL,
                [
                    'documentPrefix' => $documentPrefix,
                    'dossierNumber' => $dossierNumber,
                    'type' => $type->value,
                    'id' => $id,
                    'pageNr' => $pageNr,
                    'hash' => $hash,
                ],
            );
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

    private function makeSpreadsheetPreview(EntityWithFileInfo $entity): ?SpreadsheetPreview
    {
        if (! $entity instanceof EntityWithSpreadsheetTable) {
            return null;
        }

        $spreadsheetTable = $entity->getSpreadsheetTable();
        if (! $spreadsheetTable instanceof SpreadsheetTable) {
            return null;
        }

        return new SpreadsheetPreview($spreadsheetTable, $entity->isSpreadsheetTableTruncated());
    }

    private function getDownloadUrl(EntityWithFileInfo $entity, AbstractDossier $dossier, DossierFileType $type): string
    {
        if ($entity instanceof Document) {
            return $this->urlGenerator->generate(
                DocumentController::ROUTE_NAME_DOCUMENT_DOWNLOAD,
                ['documentNumber' => $entity->getDocumentNumber()->toString()],
            );
        }

        return $this->urlGenerator->generate(DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD, [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
            'type' => $type->value,
            'id' => $entity->getId(),
        ]);
    }
}
