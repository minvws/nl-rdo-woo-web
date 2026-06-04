<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\ComplaintJudgement\Uploads\MainDocument;

use GuzzleHttp\Psr7\Utils;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class ComplaintJudgementUploadMainDocumentRequestDtoFactory
{
    public function __invoke(
        Request $request,
        string $organisationId,
        string $dossierExternalId,
    ): ComplaintJudgementUploadMainDocumentRequestDto {
        return new ComplaintJudgementUploadMainDocumentRequestDto(
            Utils::streamFor($request->getContent(asResource: true)),
            $organisationId,
            ExternalId::create($dossierExternalId),
        );
    }
}
