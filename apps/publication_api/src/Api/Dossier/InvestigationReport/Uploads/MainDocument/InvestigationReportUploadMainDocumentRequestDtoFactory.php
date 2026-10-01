<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport\Uploads\MainDocument;

use GuzzleHttp\Psr7\Utils;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\UuidFactory;
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
            UuidFactory::create($organisationId),
            ExternalIdFactory::create($dossierExternalId),
        );
    }
}
