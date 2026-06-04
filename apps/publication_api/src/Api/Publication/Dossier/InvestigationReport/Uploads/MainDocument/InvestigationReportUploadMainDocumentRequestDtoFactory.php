<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport\Uploads\MainDocument;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class InvestigationReportUploadMainDocumentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
    ): InvestigationReportUploadMainDocumentRequestDto {
        return new InvestigationReportUploadMainDocumentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
        );
    }
}
