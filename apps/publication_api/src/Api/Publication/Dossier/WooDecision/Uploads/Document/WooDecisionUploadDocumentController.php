<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document;

use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class WooDecisionUploadDocumentController
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
        string $documentExternalId,
    ): WooDecisionUploadDocument {
        return new WooDecisionUploadDocument(
            $request->getContent(),
            $organisationId,
            ExternalId::create($dossierExternalId),
            ExternalId::create($documentExternalId),
        );
    }
}
