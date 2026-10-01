<?php

declare(strict_types=1);

namespace Shared\Service;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\PublicUrlGenerator;

readonly class DocumentDossierUrlGenerator
{
    public function __construct(
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    public function dossier(Document $document, WooDecision $dossier): string
    {
        return $this->publicUrlGenerator
            ->buildUrlFromRoute('app_document_detail', [
                'documentPrefix' => $dossier->getDocumentPrefix(),
                'dossierNumber' => $dossier->getDossierNumber(),
                'documentNumber' => $document->getDocumentNumber()->toString(),
            ])
            ->toString();
    }
}
