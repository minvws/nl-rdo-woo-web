<?php

declare(strict_types=1);

namespace Shared\Service;

use Generator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\Url;

readonly class RevokedUrlService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private PublicUrlGenerator $publicUrlGenerator,
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
                yield $url->toString();
            }
        }
    }

    /**
     * Note: some of the generated URLs depend on a file being uploaded, which might not always be the case.
     * They are included anyway, as the file might previously have been available (before suspend / withdraw).
     *
     * @return Generator<array-key,Url>
     */
    private function getDocumentUrls(Document $document): Generator
    {
        foreach ($document->getDossiers() as $dossier) {
            if ($dossier->getStatus()->isConceptOrScheduled()) {
                continue;
            }

            $documentNumber = $document->getDocumentNumber()->toString();
            $urlParams = [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
                'documentNumber' => $documentNumber,
            ];

            yield $this->publicUrlGenerator->buildUrlFromRoute('app_document_detail', $urlParams);

            unset($urlParams['documentPrefix']);

            yield $this->publicUrlGenerator->buildUrlFromRoute('app_legacy_document_detail', $urlParams);
        }
    }
}
