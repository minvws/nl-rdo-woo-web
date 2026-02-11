<?php

declare(strict_types=1);

namespace Shared\Service;

use Generator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
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
     * @return Generator<array-key,string>
     */
    public function getUrls(): Generator
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
     * @return Generator<array-key,string>
     */
    private function getDocumentUrls(Document $document): Generator
    {
        foreach ($document->getDossiers() as $dossier) {
            if ($dossier->getStatus()->isConceptOrScheduled()) {
                continue;
            }

            $urlParams = [
                'prefix' => $dossier->getDocumentPrefix(),
                'dossierId' => $dossier->getDossierNr(),
                'documentId' => $document->getDocumentNr(),
            ];

            yield $this->router->generate('app_document_detail', $urlParams);

            unset($urlParams['prefix']);

            yield $this->router->generate('app_legacy_document_detail', $urlParams);
        }
    }
}
