<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document;

use Shared\Controller\Public\Dossier\WooDecision\DocumentController;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\Url;

readonly class DocumentUrlGenerator
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    public function canonicalUrl(DocumentNumber $documentNumber): Url
    {
        return $this->publicUrlGenerator->buildUrlFromRoute(DocumentController::ROUTE_NAME_DOCUMENT_CANONICAL, [
            'documentNumber' => $documentNumber->toString(),
        ]);
    }

    public function detailUrl(Document $document, WooDecision $dossier): Url
    {
        return $this->publicUrlGenerator->buildUrlFromRoute(DocumentController::ROUTE_NAME_DOCUMENT_DETAIL, [
            'documentPrefix' => $dossier->getDocumentPrefix(),
            'dossierNumber' => $dossier->getDossierNumber(),
            'documentNumber' => $document->getDocumentNumber()->toString(),
        ]);
    }
}
