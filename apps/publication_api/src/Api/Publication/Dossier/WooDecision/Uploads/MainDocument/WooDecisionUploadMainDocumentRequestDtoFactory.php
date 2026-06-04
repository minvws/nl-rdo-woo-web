<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\MainDocument;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class WooDecisionUploadMainDocumentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
    ): WooDecisionUploadMainDocumentRequestDto {
        return new WooDecisionUploadMainDocumentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
        );
    }
}
