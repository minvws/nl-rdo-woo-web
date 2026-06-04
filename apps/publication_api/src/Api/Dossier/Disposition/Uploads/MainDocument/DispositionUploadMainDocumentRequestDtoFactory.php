<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition\Uploads\MainDocument;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class DispositionUploadMainDocumentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
    ): DispositionUploadMainDocumentRequestDto {
        return new DispositionUploadMainDocumentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
        );
    }
}
