<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Symfony\Component\Routing\RouterInterface;

readonly class RevokedUrlService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private RouterInterface $router,
        private string $publicBaseUrl,
    ) {
    }

    /**
     * @return \Generator<array-key,string>
     */
    public function getUrls(): \Generator
    {
        $revokedDocuments = $this->documentRepository->getRevokedDocumentsInPublicDossiers();
        foreach ($revokedDocuments as $document) {
            foreach ($this->getDocumentUrls($document) as $url) {
                yield $this->publicBaseUrl . $url;
            }
        }
    }

    /**
     * Note: some of the generated URLs depend on a file being uploaded, which might not always be the case.
     * They are included anyway, as the file might previously have been available (before suspend / withdraw).
     *
     * @return \Generator<array-key,string>
     */
    private function getDocumentUrls(Document $document): \Generator
    {
        foreach ($document->getDossiers() as $dossier) {
            if ($dossier->getStatus()->isConceptOrScheduled()) {
                continue;
            }

            $pageNrs = $document->getPageCount() === 0 ? [] : range(1, $document->getPageCount());
            $urlParams = [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ];

            yield $this->router->generate('app_document_detail', $urlParams);
            yield $this->router->generate('app_document_download', $urlParams);
            foreach ($pageNrs as $pageNr) {
                $urlParams['pageNr'] = $pageNr;
                yield $this->router->generate('app_document_download_page', $urlParams);
                yield $this->router->generate('app_document_thumbnail', $urlParams);
            }

            unset($urlParams['prefix'], $urlParams['pageNr']);

            yield $this->router->generate('app_legacy_document_detail', $urlParams);
            yield $this->router->generate('app_legacy_document_download', $urlParams);
            foreach ($pageNrs as $pageNr) {
                $urlParams['pageNr'] = $pageNr;
                yield $this->router->generate('app_legacy_document_download_page', $urlParams);
                yield $this->router->generate('app_legacy_document_thumbnail', $urlParams);
            }
        }
    }
}
