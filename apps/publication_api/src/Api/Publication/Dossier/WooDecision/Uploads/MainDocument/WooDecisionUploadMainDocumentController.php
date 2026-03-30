<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\MainDocument;

use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class WooDecisionUploadMainDocumentController
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
    ): WooDecisionUploadMainDocument {
        return new WooDecisionUploadMainDocument(
            content: $request->getContent(),
            organisationId: $organisationId,
            dossierExternalId: ExternalId::create($dossierExternalId),
        );
    }
}
